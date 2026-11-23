import 'dotenv/config';
import OBSWebSocket, { EventSubscription } from 'obs-websocket-js';
import Pusher from 'pusher-js';
import WebSocket from 'ws';

if (!globalThis.WebSocket) {
    globalThis.WebSocket = WebSocket;
}

const BASE_URL = (process.env.OBSISSTANT_URL || '').replace(/\/+$/, '');
const AGENT_TOKEN = (process.env.OBSISSTANT_AGENT_TOKEN || '').trim();

if (!BASE_URL || !AGENT_TOKEN) {
    console.error('[obs-agent] OBSISSTANT_URL and OBSISSTANT_AGENT_TOKEN are required in .env');
    process.exit(1);
}

const obs = new OBSWebSocket();
let obsConnected = false;
let studioConfig = null;
let reverbConfig = null;
let pusherClient = null;
let reconnectTimer = null;
let statsTimer = null;
let liveState = {
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

async function apiRequest(path, method = 'GET', body = null) {
    const headers = {
        Authorization: `Bearer ${AGENT_TOKEN}`,
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

async function pushState(partial = null, statusOverride = null) {
    try {
        const payload = {
            live_state: partial ?? liveState,
        };
        if (statusOverride) {
            payload.status = statusOverride;
        }
        await apiRequest('/api/obs-agent/state', 'POST', payload);
    } catch (error) {
        console.error('[obs-agent] Failed to push state:', error.message);
    }
}

async function hydrateSceneItems(sceneName) {
    if (!obsConnected || !sceneName) {
        return [];
    }
    try {
        const { sceneItems } = await obs.call('GetSceneItemList', { sceneName });
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

async function hydrateAudioInputs() {
    if (!obsConnected) {
        return [];
    }
    try {
        const { inputs } = await obs.call('GetInputList');
        const results = [];

        for (const input of inputs) {
            try {
                const [{ inputMuted }, vol] = await Promise.all([
                    obs.call('GetInputMute', { inputName: input.inputName }),
                    obs.call('GetInputVolume', { inputName: input.inputName }),
                ]);
                results.push({
                    inputName: input.inputName,
                    inputKind: input.inputKind,
                    inputMuted: Boolean(inputMuted),
                    inputVolumeMul: Number(vol.inputVolumeMul ?? 1),
                    inputVolumeDb: Number(vol.inputVolumeDb ?? 0),
                });
            } catch {
                // Non-audio inputs (e.g., images/color sources) don't expose mute/volume
            }
        }

        return results;
    } catch {
        return [];
    }
}

async function refreshAllState() {
    if (!obsConnected) {
        return;
    }

    try {
        const [
            version,
            sceneList,
            studioMode,
            streamStatus,
            recordStatus,
            stats,
        ] = await Promise.all([
            obs.call('GetVersion'),
            obs.call('GetSceneList'),
            obs.call('GetStudioModeEnabled'),
            obs.call('GetStreamStatus'),
            obs.call('GetRecordStatus'),
            obs.call('GetStats'),
        ]);

        let virtualCam = false;
        let replayBuffer = false;
        try {
            const vc = await obs.call('GetVirtualCamStatus');
            virtualCam = Boolean(vc.outputActive);
        } catch {
            // Virtual cam optional on some setups
        }
        try {
            const rb = await obs.call('GetReplayBufferStatus');
            replayBuffer = Boolean(rb.outputActive);
        } catch {
            // Replay buffer may be disabled in OBS profile
        }

        const programScene = sceneList.currentProgramSceneName ?? null;
        const [sceneItems, audioInputs] = await Promise.all([
            hydrateSceneItems(programScene),
            hydrateAudioInputs(),
        ]);

        liveState = {
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

        await pushState(liveState);
    } catch (error) {
        console.error('[obs-agent] State hydration error:', error.message);
    }
}

async function handlePushBrowserScreen(data) {
    const { sceneName, inputName, url, width = 1920, height = 1080 } = data;
    const { scenes } = await obs.call('GetSceneList');
    const sceneExists = scenes.some((s) => s.sceneName === sceneName);

    if (!sceneExists) {
        await obs.call('CreateScene', { sceneName });
    }

    const { sceneItems } = await obs.call('GetSceneItemList', { sceneName });
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
        await obs.call('SetInputSettings', {
            inputName,
            inputSettings: browserSettings,
            overlay: true,
        });
        sceneItemId = existingItem.sceneItemId;
        await obs.call('SetSceneItemEnabled', {
            sceneName,
            sceneItemId,
            sceneItemEnabled: true,
        });
    } else {
        const created = await obs.call('CreateInput', {
            sceneName,
            inputName,
            inputKind: 'browser_source',
            inputSettings: browserSettings,
            sceneItemEnabled: true,
        });
        sceneItemId = created.sceneItemId;
    }

    await obs.call('SetSceneItemTransform', {
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

    await refreshAllState();

    return { sceneName, inputName, sceneItemId, url };
}

async function executeCommand(payload) {
    const { command_uuid: uuid, request_type: requestType, request_data: requestData = {} } = payload;
    console.log(`[obs-agent] Executing ${requestType} (${uuid})`);

    try {
        if (!obsConnected) {
            throw new Error('OBS Studio WebSocket is not currently connected.');
        }

        let responseData = {};

        if (requestType === 'obsisstant.PushBrowserScreen') {
            responseData = await handlePushBrowserScreen(requestData);
        } else if (requestType === 'obsisstant.ExecuteBatch') {
            responseData = await obs.callBatch(requestData.requests || []);
            await refreshAllState();
        } else if (requestType === 'obsisstant.RefreshAllState') {
            await refreshAllState();
            responseData = { refreshed: true };
        } else {
            responseData = (await obs.call(requestType, requestData)) ?? {};
            setTimeout(() => refreshAllState(), 150);
        }

        await apiRequest(`/api/obs-agent/commands/${uuid}/ack`, 'POST', {
            status: 'succeeded',
            response_data: responseData,
        });
    } catch (error) {
        console.error(`[obs-agent] Command failed (${requestType}):`, error.message);
        try {
            await apiRequest(`/api/obs-agent/commands/${uuid}/ack`, 'POST', {
                status: 'failed',
                error_message: error.message,
            });
        } catch (ackError) {
            console.error('[obs-agent] Ack failed:', ackError.message);
        }
    }
}

function bindObsEvents() {
    obs.on('CurrentProgramSceneChanged', async ({ sceneName }) => {
        liveState.currentProgramScene = sceneName;
        liveState.sceneItems = await hydrateSceneItems(sceneName);
        await pushState({
            currentProgramScene: sceneName,
            sceneItems: liveState.sceneItems,
        });
    });

    obs.on('CurrentPreviewSceneChanged', async ({ sceneName }) => {
        liveState.currentPreviewScene = sceneName;
        await pushState({ currentPreviewScene: sceneName });
    });

    obs.on('StudioModeStateChanged', async ({ studioModeEnabled }) => {
        liveState.studioModeEnabled = Boolean(studioModeEnabled);
        await pushState({ studioModeEnabled: liveState.studioModeEnabled });
    });

    obs.on('SceneListChanged', async ({ scenes }) => {
        liveState.scenes = scenes;
        await pushState({ scenes });
    });

    obs.on('SceneItemEnableStateChanged', async ({ sceneName, sceneItemId, sceneItemEnabled }) => {
        if (sceneName === liveState.currentProgramScene) {
            liveState.sceneItems = liveState.sceneItems.map((item) =>
                item.sceneItemId === sceneItemId ? { ...item, sceneItemEnabled } : item
            );
            await pushState({ sceneItems: liveState.sceneItems });
        }
    });

    obs.on('InputMuteStateChanged', async ({ inputName, inputMuted }) => {
        liveState.audioInputs = liveState.audioInputs.map((inp) =>
            inp.inputName === inputName ? { ...inp, inputMuted } : inp
        );
        await pushState({ audioInputs: liveState.audioInputs });
    });

    obs.on('InputVolumeChanged', async ({ inputName, inputVolumeMul, inputVolumeDb }) => {
        liveState.audioInputs = liveState.audioInputs.map((inp) =>
            inp.inputName === inputName
                ? { ...inp, inputVolumeMul, inputVolumeDb }
                : inp
        );
        await pushState({ audioInputs: liveState.audioInputs });
    });

    obs.on('StreamStateChanged', async ({ outputActive }) => {
        liveState.outputs.streaming = Boolean(outputActive);
        await pushState({ outputs: liveState.outputs });
    });

    obs.on('RecordStateChanged', async ({ outputActive }) => {
        liveState.outputs.recording = Boolean(outputActive);
        await pushState({ outputs: liveState.outputs });
    });

    obs.on('VirtualcamStateChanged', async ({ outputActive }) => {
        liveState.outputs.virtualCam = Boolean(outputActive);
        await pushState({ outputs: liveState.outputs });
    });

    obs.on('ReplayBufferStateChanged', async ({ outputActive }) => {
        liveState.outputs.replayBuffer = Boolean(outputActive);
        await pushState({ outputs: liveState.outputs });
    });

    obs.on('ConnectionClosed', () => {
        obsConnected = false;
        console.warn('[obs-agent] OBS WebSocket closed. Scheduling reconnect...');
        pushState({ ...liveState }, 'offline');
        scheduleObsReconnect();
    });
}

function scheduleObsReconnect(delayMs = 4000) {
    if (reconnectTimer) {
        return;
    }
    reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        connectObs();
    }, delayMs);
}

async function connectObs() {
    const wsUrl =
        (process.env.OBS_WS_URL || '').trim() ||
        `ws://${studioConfig.obs_host || '127.0.0.1'}:${studioConfig.obs_port || 4455}`;
    const password = (process.env.OBS_WS_PASSWORD || '').trim() || studioConfig.obs_password || undefined;

    try {
        await obs.connect(wsUrl, password, {
            eventSubscriptions: EventSubscription.All,
            rpcVersion: 1,
        });
        obsConnected = true;
        console.log(`[obs-agent] Connected to OBS Studio at ${wsUrl}`);
        await refreshAllState();
    } catch (error) {
        obsConnected = false;
        console.warn(`[obs-agent] Waiting for OBS at ${wsUrl}: ${error.message}`);
        await apiRequest('/api/obs-agent/heartbeat', 'POST', { status: 'offline' }).catch(() => {});
        scheduleObsReconnect(5000);
    }
}

function connectReverb() {
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

    const commandChannel = pusherClient.subscribe(reverbConfig.channel);
    commandChannel.bind('pusher:subscription_succeeded', () => {
        console.log(`[obs-agent] Subscribed to Reverb channel ${reverbConfig.channel}`);
    });
    commandChannel.bind('obs.command.dispatched', (payload) => {
        executeCommand(payload);
    });
}

async function main() {
    console.log(`[obs-agent] Bootstrapping from ${BASE_URL}...`);
    const bootstrap = await apiRequest('/api/obs-agent/bootstrap');
    studioConfig = bootstrap.studio;
    reverbConfig = bootstrap.reverb;

    console.log(`[obs-agent] Studio "${studioConfig.name}" (#${studioConfig.id}) authorized.`);
    bindObsEvents();
    connectReverb();
    await connectObs();

    for (const pending of bootstrap.pending_commands || []) {
        await executeCommand(pending);
    }

    statsTimer = setInterval(async () => {
        if (obsConnected) {
            try {
                const stats = await obs.call('GetStats');
                liveState.stats = {
                    activeFps: Number((stats.activeFps ?? 0).toFixed(1)),
                    cpuUsage: Number((stats.cpuUsage ?? 0).toFixed(1)),
                    memoryUsage: Number((stats.memoryUsage ?? 0).toFixed(1)),
                    renderSkippedFrames: stats.renderSkippedFrames ?? 0,
                    outputSkippedFrames: stats.outputSkippedFrames ?? 0,
                };
                await pushState({ stats: liveState.stats });
            } catch {
                // Ignore transient stat poll errors
            }
        } else {
            await apiRequest('/api/obs-agent/heartbeat', 'POST', { status: 'offline' }).catch(() => {});
        }
    }, 10000);
}

process.on('SIGINT', () => {
    clearInterval(statsTimer);
    if (pusherClient) {
        pusherClient.disconnect();
    }
    if (obsConnected) {
        obs.disconnect().catch(() => {});
    }
    process.exit(0);
});

main().catch((err) => {
    console.error('[obs-agent] Fatal startup error:', err.message);
    process.exit(1);
});
