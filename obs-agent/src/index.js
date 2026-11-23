import 'dotenv/config';
import OBSWebSocket, { EventSubscription } from 'obs-websocket-js';
import Pusher from 'pusher-js';
import WebSocket from 'ws';

if (!globalThis.WebSocket) {
    globalThis.WebSocket = WebSocket;
}

const BASE_URL = (process.env.OBSISSTANT_URL || '').replace(/\/+$/, '');
const AUTH_TOKEN = (process.env.OBSISSTANT_RELAY_TOKEN || process.env.OBSISSTANT_AGENT_TOKEN || '').trim();

if (!BASE_URL || !AUTH_TOKEN) {
    console.error('[obs-agent] OBSISSTANT_URL and OBSISSTANT_RELAY_TOKEN (or OBSISSTANT_AGENT_TOKEN) are required in obs-agent/.env');
    process.exit(1);
}

let pusherClient = null;
let relayChannelSub = null;
let catalogPollTimer = null;
let syncInFlight = false;
let syncPending = false;
const workers = new Map();

async function apiRequest(path, method = 'GET', body = null) {
    const headers = {
        Authorization: `Bearer ${AUTH_TOKEN}`,
        Accept: 'application/json',
    };

    const options = { method, headers };
    if (body !== null) {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(`${BASE_URL}${path}`, options);
    if (!response.ok) {
        const text = await response.text();
        throw new Error(`API ${method} ${path} failed (${response.status}): ${text}`);
    }

    return response.json();
}

function emptyLiveState() {
    return {
        obsVersion: null,
        websocketVersion: null,
        currentProgramScene: null,
        currentPreviewScene: null,
        studioModeEnabled: false,
        scenes: [],
        sceneItems: [],
        audioInputs: [],
        outputs: {
            streaming: false,
            recording: false,
            recordingPaused: false,
            virtualCam: false,
            replayBuffer: false,
        },
        stats: {},
    };
}

class StudioWorker {
    constructor(entry) {
        this.studio = entry.studio;
        this.channelName = entry.channel;
        this.pendingCommands = entry.pending_commands || [];
        this.obs = new OBSWebSocket();
        this.obsConnected = false;
        this.stopped = false;
        this.reconnectTimer = null;
        this.statsTimer = null;
        this.commandChannel = null;
        this.liveState = emptyLiveState();
        this.signature = this.buildSignature(this.studio);
        this.bindObsEvents();
    }

    buildSignature(studio) {
        return `${studio.obs_host || '127.0.0.1'}:${studio.obs_port || 4455}:${studio.obs_password || ''}`;
    }

    logPrefix() {
        return `[obs-agent][${this.studio.name} #${this.studio.id}]`;
    }

    async pushState(partial = null, statusOverride = null) {
        if (this.stopped) {
            return;
        }
        try {
            const payload = {
                studio_id: this.studio.id,
                live_state: partial ?? this.liveState,
            };
            if (statusOverride) {
                payload.status = statusOverride;
            }
            await apiRequest('/api/obs-agent/state', 'POST', payload);
        } catch (error) {
            console.error(`${this.logPrefix()} Failed to push state:`, error.message);
        }
    }

    async pushHeartbeat(status = 'offline') {
        if (this.stopped) {
            return;
        }
        try {
            await apiRequest('/api/obs-agent/heartbeat', 'POST', {
                studio_id: this.studio.id,
                status,
            });
        } catch {
            // Transient heartbeat error
        }
    }

    async hydrateSceneItems(sceneName) {
        if (!this.obsConnected || !sceneName) {
            return [];
        }
        try {
            const { sceneItems } = await this.obs.call('GetSceneItemList', { sceneName });
            return sceneItems.map((item) => ({
                sceneItemId: item.sceneItemId,
                sourceName: item.sourceName,
                inputKind: item.inputKind ?? null,
                sceneItemEnabled: Boolean(item.sceneItemEnabled),
                sceneItemLocked: Boolean(item.sceneItemLocked),
                sceneItemTransform: item.sceneItemTransform ?? {},
            }));
        } catch {
            return [];
        }
    }

    async hydrateAudioInputs() {
        if (!this.obsConnected) {
            return [];
        }
        try {
            const { inputs } = await this.obs.call('GetInputList');
            const results = [];

            for (const input of inputs) {
                try {
                    const [{ inputMuted }, vol] = await Promise.all([
                        this.obs.call('GetInputMute', { inputName: input.inputName }),
                        this.obs.call('GetInputVolume', { inputName: input.inputName }),
                    ]);
                    results.push({
                        inputName: input.inputName,
                        inputKind: input.inputKind,
                        inputMuted: Boolean(inputMuted),
                        inputVolumeMul: Number(vol.inputVolumeMul ?? 1),
                        inputVolumeDb: Number(vol.inputVolumeDb ?? 0),
                    });
                } catch {
                    // Non-audio inputs don't expose mute/volume
                }
            }

            return results;
        } catch {
            return [];
        }
    }

    async refreshAllState() {
        if (!this.obsConnected || this.stopped) {
            return;
        }

        try {
            const [version, sceneList, studioMode, streamStatus, recordStatus, stats] = await Promise.all([
                this.obs.call('GetVersion'),
                this.obs.call('GetSceneList'),
                this.obs.call('GetStudioModeEnabled'),
                this.obs.call('GetStreamStatus'),
                this.obs.call('GetRecordStatus'),
                this.obs.call('GetStats'),
            ]);

            let virtualCam = false;
            let replayBuffer = false;
            try {
                const vc = await this.obs.call('GetVirtualCamStatus');
                virtualCam = Boolean(vc.outputActive);
            } catch {
                // Optional on some setups
            }
            try {
                const rb = await this.obs.call('GetReplayBufferStatus');
                replayBuffer = Boolean(rb.outputActive);
            } catch {
                // Replay buffer may be disabled
            }

            const programScene = sceneList.currentProgramSceneName ?? null;
            const [sceneItems, audioInputs] = await Promise.all([
                this.hydrateSceneItems(programScene),
                this.hydrateAudioInputs(),
            ]);

            this.liveState = {
                obsVersion: version.obsVersion,
                websocketVersion: version.obsWebSocketVersion,
                currentProgramScene: programScene,
                currentPreviewScene: sceneList.currentPreviewSceneName ?? null,
                studioModeEnabled: Boolean(studioMode.studioModeEnabled),
                scenes: sceneList.scenes ?? [],
                sceneItems,
                audioInputs,
                outputs: {
                    streaming: Boolean(streamStatus.outputActive),
                    streamTimecode: streamStatus.outputTimecode ?? null,
                    recording: Boolean(recordStatus.outputActive),
                    recordingPaused: Boolean(recordStatus.outputPaused),
                    recordTimecode: recordStatus.outputTimecode ?? null,
                    virtualCam,
                    replayBuffer,
                },
                stats: {
                    activeFps: Number((stats.activeFps ?? 0).toFixed(1)),
                    cpuUsage: Number((stats.cpuUsage ?? 0).toFixed(1)),
                    memoryUsage: Number((stats.memoryUsage ?? 0).toFixed(1)),
                    renderSkippedFrames: stats.renderSkippedFrames ?? 0,
                    outputSkippedFrames: stats.outputSkippedFrames ?? 0,
                },
            };

            await this.pushState(this.liveState);
        } catch (error) {
            console.error(`${this.logPrefix()} State hydration error:`, error.message);
        }
    }

    async handlePushBrowserScreen(data) {
        const { sceneName, inputName, url, width = 1920, height = 1080 } = data;
        const { scenes } = await this.obs.call('GetSceneList');
        const sceneExists = scenes.some((s) => s.sceneName === sceneName);

        if (!sceneExists) {
            await this.obs.call('CreateScene', { sceneName });
        }

        const { sceneItems } = await this.obs.call('GetSceneItemList', { sceneName });
        const existingItem = sceneItems.find((item) => item.sourceName === inputName);

        const browserSettings = {
            url,
            width: Number(width),
            height: Number(height),
            fps: 60,
            reroute_audio: false,
            restart_when_active: false,
        };

        let sceneItemId;
        if (existingItem) {
            await this.obs.call('SetInputSettings', {
                inputName,
                inputSettings: browserSettings,
                overlay: true,
            });
            sceneItemId = existingItem.sceneItemId;
            await this.obs.call('SetSceneItemEnabled', {
                sceneName,
                sceneItemId,
                sceneItemEnabled: true,
            });
        } else {
            const created = await this.obs.call('CreateInput', {
                sceneName,
                inputName,
                inputKind: 'browser_source',
                inputSettings: browserSettings,
                sceneItemEnabled: true,
            });
            sceneItemId = created.sceneItemId;
        }

        await this.obs.call('SetSceneItemTransform', {
            sceneName,
            sceneItemId,
            sceneItemTransform: {
                positionX: 0,
                positionY: 0,
                boundsType: 'OBS_BOUNDS_SCALE_INNER',
                boundsWidth: Number(width),
                boundsHeight: Number(height),
            },
        });

        await this.refreshAllState();

        return { sceneName, inputName, sceneItemId, url };
    }

    async executeCommand(payload) {
        const { command_uuid: uuid, request_type: requestType, request_data: requestData = {} } = payload;
        console.log(`${this.logPrefix()} Executing ${requestType} (${uuid})`);

        try {
            if (!this.obsConnected) {
                throw new Error('OBS Studio WebSocket is not currently connected.');
            }

            let responseData = {};

            if (requestType === 'obsisstant.PushBrowserScreen') {
                responseData = await this.handlePushBrowserScreen(requestData);
            } else if (requestType === 'obsisstant.ExecuteBatch') {
                responseData = await this.obs.callBatch(requestData.requests || []);
                await this.refreshAllState();
            } else if (requestType === 'obsisstant.RefreshAllState') {
                await this.refreshAllState();
                responseData = { refreshed: true };
            } else {
                responseData = (await this.obs.call(requestType, requestData)) ?? {};
                setTimeout(() => this.refreshAllState(), 150);
            }

            await apiRequest(`/api/obs-agent/commands/${uuid}/ack`, 'POST', {
                studio_id: this.studio.id,
                status: 'succeeded',
                response_data: responseData,
            });
        } catch (error) {
            console.error(`${this.logPrefix()} Command failed (${requestType}):`, error.message);
            try {
                await apiRequest(`/api/obs-agent/commands/${uuid}/ack`, 'POST', {
                    studio_id: this.studio.id,
                    status: 'failed',
                    error_message: error.message,
                });
            } catch (ackError) {
                console.error(`${this.logPrefix()} Ack failed:`, ackError.message);
            }
        }
    }

    bindObsEvents() {
        this.obs.on('CurrentProgramSceneChanged', async ({ sceneName }) => {
            this.liveState.currentProgramScene = sceneName;
            this.liveState.sceneItems = await this.hydrateSceneItems(sceneName);
            await this.pushState({
                currentProgramScene: sceneName,
                sceneItems: this.liveState.sceneItems,
            });
        });

        this.obs.on('CurrentPreviewSceneChanged', async ({ sceneName }) => {
            this.liveState.currentPreviewScene = sceneName;
            await this.pushState({ currentPreviewScene: sceneName });
        });

        this.obs.on('StudioModeStateChanged', async ({ studioModeEnabled }) => {
            this.liveState.studioModeEnabled = Boolean(studioModeEnabled);
            await this.pushState({ studioModeEnabled: this.liveState.studioModeEnabled });
        });

        this.obs.on('SceneListChanged', async ({ scenes }) => {
            this.liveState.scenes = scenes;
            await this.pushState({ scenes });
        });

        this.obs.on('SceneItemEnableStateChanged', async ({ sceneName, sceneItemId, sceneItemEnabled }) => {
            if (sceneName === this.liveState.currentProgramScene) {
                this.liveState.sceneItems = this.liveState.sceneItems.map((item) =>
                    item.sceneItemId === sceneItemId ? { ...item, sceneItemEnabled } : item
                );
                await this.pushState({ sceneItems: this.liveState.sceneItems });
            }
        });

        this.obs.on('InputMuteStateChanged', async ({ inputName, inputMuted }) => {
            this.liveState.audioInputs = this.liveState.audioInputs.map((inp) =>
                inp.inputName === inputName ? { ...inp, inputMuted } : inp
            );
            await this.pushState({ audioInputs: this.liveState.audioInputs });
        });

        this.obs.on('InputVolumeChanged', async ({ inputName, inputVolumeMul, inputVolumeDb }) => {
            this.liveState.audioInputs = this.liveState.audioInputs.map((inp) =>
                inp.inputName === inputName ? { ...inp, inputVolumeMul, inputVolumeDb } : inp
            );
            await this.pushState({ audioInputs: this.liveState.audioInputs });
        });

        this.obs.on('StreamStateChanged', async ({ outputActive }) => {
            this.liveState.outputs.streaming = Boolean(outputActive);
            await this.pushState({ outputs: this.liveState.outputs });
        });

        this.obs.on('RecordStateChanged', async ({ outputActive }) => {
            this.liveState.outputs.recording = Boolean(outputActive);
            await this.pushState({ outputs: this.liveState.outputs });
        });

        this.obs.on('VirtualcamStateChanged', async ({ outputActive }) => {
            this.liveState.outputs.virtualCam = Boolean(outputActive);
            await this.pushState({ outputs: this.liveState.outputs });
        });

        this.obs.on('ReplayBufferStateChanged', async ({ outputActive }) => {
            this.liveState.outputs.replayBuffer = Boolean(outputActive);
            await this.pushState({ outputs: this.liveState.outputs });
        });

        this.obs.on('ConnectionClosed', () => {
            this.obsConnected = false;
            if (this.stopped) {
                return;
            }
            console.warn(`${this.logPrefix()} OBS WebSocket closed. Scheduling reconnect...`);
            this.pushState({ ...this.liveState }, 'offline');
            this.scheduleObsReconnect();
        });
    }

    scheduleObsReconnect(delayMs = 5000) {
        if (this.stopped || this.reconnectTimer) {
            return;
        }
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.connectObs();
        }, delayMs);
    }

    async connectObs() {
        if (this.stopped) {
            return;
        }
        const wsUrl =
            (process.env.OBS_WS_URL || '').trim() ||
            `ws://${this.studio.obs_host || '127.0.0.1'}:${this.studio.obs_port || 4455}`;
        const password = (process.env.OBS_WS_PASSWORD || '').trim() || this.studio.obs_password || undefined;

        try {
            await this.obs.connect(wsUrl, password, {
                eventSubscriptions: EventSubscription.All,
                rpcVersion: 1,
            });
            this.obsConnected = true;
            console.log(`${this.logPrefix()} Connected to OBS Studio at ${wsUrl}`);
            await this.refreshAllState();
        } catch (error) {
            this.obsConnected = false;
            console.warn(`${this.logPrefix()} Waiting for OBS at ${wsUrl}: ${error.message}`);
            await this.pushHeartbeat('offline');
            this.scheduleObsReconnect(5000);
        }
    }

    subscribeCommandChannel() {
        if (!pusherClient || this.commandChannel) {
            return;
        }
        this.commandChannel = pusherClient.subscribe(this.channelName);
        this.commandChannel.bind('pusher:subscription_succeeded', () => {
            console.log(`${this.logPrefix()} Subscribed to Reverb ${this.channelName}`);
        });
        this.commandChannel.bind('obs.command.dispatched', (payload) => {
            this.executeCommand(payload);
        });
    }

    async start() {
        this.subscribeCommandChannel();
        await this.connectObs();

        for (const pending of this.pendingCommands) {
            await this.executeCommand(pending);
        }
        this.pendingCommands = [];

        this.statsTimer = setInterval(async () => {
            if (this.stopped) {
                return;
            }
            if (this.obsConnected) {
                try {
                    const stats = await this.obs.call('GetStats');
                    this.liveState.stats = {
                        activeFps: Number((stats.activeFps ?? 0).toFixed(1)),
                        cpuUsage: Number((stats.cpuUsage ?? 0).toFixed(1)),
                        memoryUsage: Number((stats.memoryUsage ?? 0).toFixed(1)),
                        renderSkippedFrames: stats.renderSkippedFrames ?? 0,
                        outputSkippedFrames: stats.outputSkippedFrames ?? 0,
                    };
                    await this.pushState({ stats: this.liveState.stats });
                } catch {
                    // Ignore transient stat poll errors
                }
            } else {
                await this.pushHeartbeat('offline');
            }
        }, 10000);
    }

    async updateConfig(entry) {
        const nextSignature = this.buildSignature(entry.studio);
        const oldName = this.studio.name;
        this.studio = entry.studio;

        if (nextSignature !== this.signature) {
            this.signature = nextSignature;
            console.log(`${this.logPrefix()} Connection profile updated (${oldName} -> ${this.studio.name}). Reconnecting OBS...`);
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }
            if (this.obsConnected) {
                this.obsConnected = false;
                await this.obs.disconnect().catch(() => {});
            }
            await this.connectObs();
        }
    }

    async stop() {
        this.stopped = true;
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        if (this.statsTimer) {
            clearInterval(this.statsTimer);
            this.statsTimer = null;
        }
        if (this.commandChannel && pusherClient) {
            pusherClient.unsubscribe(this.channelName);
            this.commandChannel = null;
        }
        if (this.obsConnected) {
            this.obsConnected = false;
            await this.obs.disconnect().catch(() => {});
        }
        console.log(`${this.logPrefix()} Worker stopped.`);
    }
}

function ensurePusherClient(reverbConfig) {
    if (pusherClient) {
        return;
    }

    const useTLS = reverbConfig.scheme === 'https';

    pusherClient = new Pusher(reverbConfig.key, {
        cluster: '',
        wsHost: reverbConfig.host,
        wsPort: reverbConfig.port,
        wssPort: reverbConfig.port,
        forceTLS: useTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authorizer: (channel) => ({
            authorize: async (socketId, callback) => {
                try {
                    const data = await apiRequest('/api/obs-agent/broadcasting/auth', 'POST', {
                        socket_id: socketId,
                        channel_name: channel.name,
                    });
                    callback(null, data);
                } catch (err) {
                    callback(err, null);
                }
            },
        }),
    });
}

async function reconcileStudios(studiosPayload) {
    const seenIds = new Set();

    for (const entry of studiosPayload) {
        const id = entry.studio.id;
        seenIds.add(id);

        if (workers.has(id)) {
            await workers.get(id).updateConfig(entry);
        } else {
            const worker = new StudioWorker(entry);
            workers.set(id, worker);
            worker.start();
        }
    }

    for (const [id, worker] of workers.entries()) {
        if (!seenIds.has(id)) {
            await worker.stop();
            workers.delete(id);
        }
    }
}

async function syncFromBootstrap() {
    if (syncInFlight) {
        syncPending = true;
        return null;
    }

    syncInFlight = true;
    try {
        const bootstrap = await apiRequest('/api/obs-agent/bootstrap');
        ensurePusherClient(bootstrap.reverb);

        const entries = Array.isArray(bootstrap.studios)
            ? bootstrap.studios
            : [{ studio: bootstrap.studio, channel: bootstrap.reverb.channel, pending_commands: bootstrap.pending_commands || [] }];

        await reconcileStudios(entries);
        return bootstrap;
    } finally {
        syncInFlight = false;
        if (syncPending) {
            syncPending = false;
            setTimeout(() => syncFromBootstrap().catch((e) => console.error('[obs-agent] Resync error:', e.message)), 250);
        }
    }
}

async function main() {
    console.log(`[obs-agent] Bootstrapping from ${BASE_URL}...`);
    const bootstrap = await syncFromBootstrap();

    if (bootstrap?.mode === 'relay') {
        const relayChannel = bootstrap.reverb.relay_channel || 'private-obs.relay';
        relayChannelSub = pusherClient.subscribe(relayChannel);
        relayChannelSub.bind('pusher:subscription_succeeded', () => {
            console.log(`[obs-agent] Workspace Relay active on ${relayChannel} (${workers.size} studio(s) synced).`);
        });
        relayChannelSub.bind('obs.relay.catalog-updated', () => {
            console.log('[obs-agent] Studio catalog updated in Obsisstant UI — resyncing...');
            syncFromBootstrap().catch((err) => console.error('[obs-agent] Catalog resync error:', err.message));
        });

        catalogPollTimer = setInterval(() => {
            syncFromBootstrap().catch((err) => console.error('[obs-agent] Periodic catalog check error:', err.message));
        }, 30000);
    } else if (bootstrap?.studio) {
        console.log(`[obs-agent] Single-Studio mode active for "${bootstrap.studio.name}" (#${bootstrap.studio.id}).`);
    }
}

async function shutdown() {
    clearInterval(catalogPollTimer);
    for (const worker of workers.values()) {
        await worker.stop();
    }
    workers.clear();
    if (pusherClient) {
        pusherClient.disconnect();
    }
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

main().catch((err) => {
    console.error('[obs-agent] Fatal startup error:', err.message);
    process.exit(1);
});
