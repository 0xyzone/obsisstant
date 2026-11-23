import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import dotenv from 'dotenv';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import OBSWebSocket, { EventSubscription } from 'obs-websocket-js';

const execFileAsync = promisify(execFile);
import Pusher from 'pusher-js';
import WebSocket from 'ws';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, '../.env') });

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
        currentSceneCollection: null,
        sceneCollections: [],
        currentProgramScene: null,
        currentPreviewScene: null,
        studioModeEnabled: false,
        videoDimensions: {
            width: 1920,
            height: 1080,
        },
        scenes: [],
        sceneItems: [],
        sceneItemsByScene: {},
        audioInputs: [],
        allInputs: [],
        inputKinds: [],
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
        this.studioChannelName = entry.studio_channel || `private-obs.studio.${entry.studio.id}`;
        this.pendingCommands = entry.pending_commands || [];
        this.obs = new OBSWebSocket();
        this.obsConnected = false;
        this.stopped = false;
        this.reconnectTimer = null;
        this.statsTimer = null;
        this.refreshDebounceTimer = null;
        this.refreshInFlight = false;
        this.refreshQueued = false;
        this.partialPushTimer = null;
        this.previewTimer = null;
        this.previewInFlight = false;
        this.lastPreviewHash = null;
        this.lastPreviewUploadedAt = 0;
        this.lastStatsSignature = '';
        this.lastHeartbeatAt = 0;
        this.previewConfig = {
            active: false,
            sceneName: null,
            intervalMs: 2500,
            width: 640,
            expiresAt: 0,
        };
        this.commandChannel = null;
        this.studioChannel = null;
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
            this.lastHeartbeatAt = Date.now();
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
            this.lastHeartbeatAt = Date.now();
        } catch {
            // Transient heartbeat error
        }
    }

    schedulePartialScenePush(sceneName) {
        if (this.stopped || !this.obsConnected) {
            return;
        }
        if (this.partialPushTimer) {
            clearTimeout(this.partialPushTimer);
        }
        this.partialPushTimer = setTimeout(async () => {
            this.partialPushTimer = null;
            const partial = {
                sceneItemsByScene: {
                    ...this.liveState.sceneItemsByScene,
                },
            };
            if (sceneName === this.liveState.currentProgramScene) {
                partial.sceneItems = this.liveState.sceneItemsByScene[sceneName] ?? [];
            }
            this.lastPreviewHash = null;
            await this.pushState(partial);
            if (this.previewConfig.active) {
                this.captureAndUploadPreviewFrame(true);
            }
        }, 200);
    }

    patchSceneItemTransform(sceneName, sceneItemId, sceneItemTransform) {
        const list = this.liveState.sceneItemsByScene?.[sceneName];
        if (!Array.isArray(list)) {
            this.scheduleRefreshAllState(200);
            return;
        }

        let matched = false;
        const updated = list.map((item) => {
            if (Number(item.sceneItemId) !== Number(sceneItemId)) {
                return item;
            }
            matched = true;
            return {
                ...item,
                sceneItemTransform: {
                    ...(item.sceneItemTransform || {}),
                    ...(sceneItemTransform || {}),
                },
            };
        });

        if (!matched) {
            this.scheduleRefreshAllState(200);
            return;
        }

        this.liveState.sceneItemsByScene[sceneName] = updated;
        if (sceneName === this.liveState.currentProgramScene) {
            this.liveState.sceneItems = updated;
        }
        this.schedulePartialScenePush(sceneName);
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
                sourceType: item.sourceType ?? null,
                inputKind: item.inputKind ?? null,
                sceneItemIndex: item.sceneItemIndex ?? 0,
                sceneItemEnabled: Boolean(item.sceneItemEnabled),
                sceneItemLocked: Boolean(item.sceneItemLocked),
                sceneItemTransform: item.sceneItemTransform ?? {},
            }));
        } catch {
            return [];
        }
    }

    async hydrateAllScenesItems(scenes) {
        const entries = await Promise.all(
            scenes
                .map((scene) => scene?.sceneName)
                .filter(Boolean)
                .map(async (sName) => [sName, await this.hydrateSceneItems(sName)])
        );

        return Object.fromEntries(entries);
    }

    async hydrateInputsMetadata() {
        if (!this.obsConnected) {
            return { allInputs: [], audioInputs: [], inputKinds: [] };
        }

        try {
            const [{ inputs }, kindsRes] = await Promise.all([
                this.obs.call('GetInputList'),
                this.obs.call('GetInputKindList', { unversioned: false }).catch(() => ({ inputKinds: [] })),
            ]);

            const allInputs = inputs.map((input) => ({
                inputName: input.inputName,
                inputKind: input.inputKind,
            }));

            const audioResults = await Promise.all(
                inputs.map(async (input) => {
                    try {
                        const [{ inputMuted }, vol] = await Promise.all([
                            this.obs.call('GetInputMute', { inputName: input.inputName }),
                            this.obs.call('GetInputVolume', { inputName: input.inputName }),
                        ]);
                        return {
                            inputName: input.inputName,
                            inputKind: input.inputKind,
                            inputMuted: Boolean(inputMuted),
                            inputVolumeMul: Number(vol.inputVolumeMul ?? 1),
                            inputVolumeDb: Number(vol.inputVolumeDb ?? 0),
                        };
                    } catch {
                        return null;
                    }
                })
            );

            return {
                allInputs,
                audioInputs: audioResults.filter(Boolean),
                inputKinds: Array.isArray(kindsRes?.inputKinds) ? kindsRes.inputKinds : [],
            };
        } catch {
            return { allInputs: [], audioInputs: [], inputKinds: [] };
        }
    }

    async refreshAllState() {
        if (!this.obsConnected || this.stopped) {
            return;
        }
        if (this.refreshInFlight) {
            this.refreshQueued = true;
            return;
        }

        this.refreshInFlight = true;
        try {
            const [version, sceneList, collectionList, studioMode, streamStatus, recordStatus, stats, videoSettings] = await Promise.all([
                this.obs.call('GetVersion'),
                this.obs.call('GetSceneList'),
                this.obs.call('GetSceneCollectionList').catch(() => ({ currentSceneCollectionName: null, sceneCollections: [] })),
                this.obs.call('GetStudioModeEnabled'),
                this.obs.call('GetStreamStatus'),
                this.obs.call('GetRecordStatus'),
                this.obs.call('GetStats'),
                this.obs.call('GetVideoSettings').catch(() => null),
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

            const scenes = sceneList.scenes ?? [];
            const programScene = sceneList.currentProgramSceneName ?? null;
            const [sceneItemsByScene, inputMeta] = await Promise.all([
                this.hydrateAllScenesItems(scenes),
                this.hydrateInputsMetadata(),
            ]);

            this.liveState = {
                obsVersion: version.obsVersion,
                websocketVersion: version.obsWebSocketVersion,
                currentSceneCollection: collectionList?.currentSceneCollectionName ?? null,
                sceneCollections: Array.isArray(collectionList?.sceneCollections) ? collectionList.sceneCollections : [],
                currentProgramScene: programScene,
                currentPreviewScene: sceneList.currentPreviewSceneName ?? null,
                studioModeEnabled: Boolean(studioMode.studioModeEnabled),
                videoDimensions: {
                    width: Number(videoSettings?.baseWidth || 1920),
                    height: Number(videoSettings?.baseHeight || 1080),
                },
                scenes,
                sceneItems: programScene ? (sceneItemsByScene[programScene] ?? []) : [],
                sceneItemsByScene,
                audioInputs: inputMeta.audioInputs,
                allInputs: inputMeta.allInputs,
                inputKinds: inputMeta.inputKinds,
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
                agentHost: {
                    platform: process.platform,
                    homeDir: os.homedir(),
                    defaultMediaDir: path.join(os.homedir(), 'Pictures', 'Obsisstant'),
                },
            };

            await this.pushState(this.liveState);
            this.lastPreviewHash = null;
            if (this.previewConfig.active) {
                this.captureAndUploadPreviewFrame(true);
            }
        } catch (error) {
            console.error(`${this.logPrefix()} State hydration error:`, error.message);
        } finally {
            this.refreshInFlight = false;
            if (this.refreshQueued) {
                this.refreshQueued = false;
                this.scheduleRefreshAllState(120);
            }
        }
    }

    scheduleRefreshAllState(delayMs = 150) {
        if (this.stopped || !this.obsConnected) {
            return;
        }
        if (this.refreshDebounceTimer) {
            clearTimeout(this.refreshDebounceTimer);
        }
        this.refreshDebounceTimer = setTimeout(() => {
            this.refreshDebounceTimer = null;
            this.refreshAllState();
        }, delayMs);
    }

    stopPreviewLoop() {
        this.previewConfig.active = false;
        this.lastPreviewHash = null;
        if (this.previewTimer) {
            clearInterval(this.previewTimer);
            this.previewTimer = null;
        }
    }

    async captureAndUploadPreviewFrame(force = false) {
        if (this.stopped || !this.obsConnected || !this.previewConfig.active || this.previewInFlight) {
            return;
        }
        if (Date.now() > this.previewConfig.expiresAt) {
            this.stopPreviewLoop();
            return;
        }

        const targetScene = this.previewConfig.sceneName || this.liveState.currentProgramScene;
        if (!targetScene) {
            return;
        }

        this.previewInFlight = true;
        try {
            const shot = await this.obs.call('GetSourceScreenshot', {
                sourceName: targetScene,
                imageFormat: 'jpeg',
                imageWidth: Math.min(1280, Math.max(320, Number(this.previewConfig.width || 640))),
                imageCompressionQuality: 52,
            });

            if (shot?.imageData) {
                const frameHash = crypto
                    .createHash('sha1')
                    .update(`${targetScene}:${shot.imageData}`)
                    .digest('hex');

                const staleHeartbeat = Date.now() - this.lastPreviewUploadedAt >= 15000;
                if (!force && frameHash === this.lastPreviewHash && !staleHeartbeat) {
                    return;
                }

                // Prefer direct peer-to-Reverb WebSocket whispers (0 HTTP/PHP requests per frame)
                let sentViaWebSocket = false;
                if (this.studioChannel?.subscribed && shot.imageData.length <= 180000) {
                    try {
                        sentViaWebSocket = Boolean(
                            this.studioChannel.trigger('client-obs.preview.frame', {
                                studio_id: this.studio.id,
                                scene_name: targetScene,
                                version: String(Date.now()),
                                image_data: shot.imageData,
                            })
                        );
                    } catch {
                        sentViaWebSocket = false;
                    }
                }

                if (!sentViaWebSocket) {
                    await apiRequest('/api/obs-agent/preview-frame', 'POST', {
                        studio_id: this.studio.id,
                        scene_name: targetScene,
                        image_data: shot.imageData,
                    });
                }

                this.lastPreviewHash = frameHash;
                this.lastPreviewUploadedAt = Date.now();
            }
        } catch {
            // Skip frame if scene temporarily unavailable
        } finally {
            this.previewInFlight = false;
        }
    }

    handleSetPreviewStream(data) {
        const active = Boolean(data?.active);
        if (!active) {
            this.stopPreviewLoop();
            return { active: false };
        }

        const nextScene = data?.sceneName || null;
        const intervalMs = Math.min(10000, Math.max(800, Number(data?.intervalMs || 2500)));
        const nextWidth = Math.min(1280, Math.max(320, Number(data?.width || 640)));
        const wasSameStream = this.previewConfig.active
            && this.previewConfig.sceneName === nextScene
            && this.previewConfig.intervalMs === intervalMs
            && this.previewConfig.width === nextWidth
            && Boolean(this.previewTimer);

        this.previewConfig = {
            active: true,
            sceneName: nextScene,
            intervalMs,
            width: nextWidth,
            expiresAt: Date.now() + 45000,
        };

        if (wasSameStream) {
            return { active: true, sceneName: nextScene, intervalMs, renewed: true };
        }

        this.lastPreviewHash = null;
        if (this.previewTimer) {
            clearInterval(this.previewTimer);
        }

        this.captureAndUploadPreviewFrame(true);
        this.previewTimer = setInterval(() => {
            this.captureAndUploadPreviewFrame(false);
        }, intervalMs);

        return { active: true, sceneName: this.previewConfig.sceneName, intervalMs };
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

    expandUserPath(inputPath) {
        const defaultDir = path.join(os.homedir(), 'Pictures', 'Obsisstant');
        const raw = String(inputPath || '').trim() || defaultDir;
        if (raw === '~') {
            return os.homedir();
        }
        if (raw.startsWith('~/') || raw.startsWith('~\\')) {
            return path.join(os.homedir(), raw.slice(2));
        }

        return path.resolve(raw);
    }

    async handleSaveMediaFile(data) {
        const targetDir = this.expandUserPath(data?.targetDir);
        const filename = path.basename(String(data?.filename || `obs-upload-${Date.now()}.png`));
        const downloadUrl = String(data?.downloadUrl || '');
        if (!downloadUrl) {
            throw new Error('Missing uploaded media download URL.');
        }

        const parsedDownload = new URL(downloadUrl);
        if (parsedDownload.origin !== new URL(BASE_URL).origin) {
            throw new Error('Refusing media download from mismatched server origin.');
        }

        const res = await fetch(downloadUrl, {
            headers: { Authorization: `Bearer ${AUTH_TOKEN}` },
        });
        if (!res.ok) {
            throw new Error(`Failed downloading staged image (${res.status}).`);
        }

        const bytes = Buffer.from(await res.arrayBuffer());
        await fs.promises.mkdir(targetDir, { recursive: true });
        const destination = path.join(targetDir, filename);
        await fs.promises.writeFile(destination, bytes);

        return {
            file: destination,
            targetDir,
            filename,
            bytes: bytes.length,
        };
    }

    async handlePickLocalDirectory(data) {
        const prompt = String(data?.prompt || 'Select Obsisstant OBS Image Storage Folder').replace(/["\\]/g, '');

        if (process.platform === 'darwin') {
            const script = [
                'tell application "System Events" to activate',
                `POSIX path of (choose folder with prompt "${prompt}")`,
            ].join('\n');

            try {
                const { stdout } = await execFileAsync('osascript', ['-e', script], { timeout: 60000 });
                const picked = stdout.trim().replace(/\/+$/, '');
                return { path: picked, cancelled: picked === '' };
            } catch (error) {
                const msg = String(error?.stderr || error?.message || '');
                if (msg.includes('-128') || msg.toLowerCase().includes('cancel')) {
                    return { cancelled: true };
                }
                throw new Error(`macOS Finder folder picker failed: ${msg}`);
            }
        }

        if (process.platform === 'win32') {
            const ps = [
                'Add-Type -AssemblyName System.Windows.Forms;',
                '$f = New-Object System.Windows.Forms.FolderBrowserDialog;',
                `$f.Description = "${prompt}";`,
                'if ($f.ShowDialog() -eq [System.Windows.Forms.DialogResult]::OK) { $f.SelectedPath }',
            ].join(' ');
            const { stdout } = await execFileAsync('powershell', ['-NoProfile', '-Command', ps], { timeout: 60000 });
            const picked = stdout.trim();
            return picked ? { path: picked, cancelled: false } : { cancelled: true };
        }

        const { stdout } = await execFileAsync('zenity', ['--file-selection', '--directory', `--title=${prompt}`], { timeout: 60000 });
        const picked = stdout.trim();
        return picked ? { path: picked, cancelled: false } : { cancelled: true };
    }

    normalizeObsPayload(requestType, rawData) {
        const data = rawData && typeof rawData === 'object' && !Array.isArray(rawData)
            ? { ...rawData }
            : {};

        if (requestType === 'CreateInput' || requestType === 'SetInputSettings') {
            if (!data.inputSettings || typeof data.inputSettings !== 'object' || Array.isArray(data.inputSettings)) {
                data.inputSettings = {};
            }
        }

        if (requestType === 'SetSceneItemTransform') {
            if (!data.sceneItemTransform || typeof data.sceneItemTransform !== 'object' || Array.isArray(data.sceneItemTransform)) {
                data.sceneItemTransform = {};
            }
            if (data.sceneItemId !== undefined) {
                data.sceneItemId = Number(data.sceneItemId);
            }
        }

        if (['SetSceneItemEnabled', 'SetSceneItemLocked', 'SetSceneItemIndex', 'RemoveSceneItem'].includes(requestType)) {
            if (data.sceneItemId !== undefined) {
                data.sceneItemId = Number(data.sceneItemId);
            }
            if (data.sceneItemIndex !== undefined) {
                data.sceneItemIndex = Number(data.sceneItemIndex);
            }
        }

        return data;
    }

    async executeCommand(payload) {
        const { command_uuid: uuid, request_type: requestType, request_data: requestData = {} } = payload;
        const normalizedData = this.normalizeObsPayload(requestType, requestData);
        if (requestType !== 'obsisstant.SetPreviewStream') {
            console.log(`${this.logPrefix()} Executing ${requestType} (${uuid})`);
        }

        try {
            if (!this.obsConnected) {
                throw new Error('OBS Studio WebSocket is not currently connected.');
            }

            let responseData = {};

            if (requestType === 'obsisstant.PushBrowserScreen') {
                responseData = await this.handlePushBrowserScreen(normalizedData);
            } else if (requestType === 'obsisstant.SaveMediaFile') {
                responseData = await this.handleSaveMediaFile(normalizedData);
            } else if (requestType === 'obsisstant.PickLocalDirectory') {
                responseData = await this.handlePickLocalDirectory(normalizedData);
            } else if (requestType === 'obsisstant.FetchInputSettings') {
                const settingsRes = await this.obs.call('GetInputSettings', {
                    inputName: normalizedData.inputName,
                });
                responseData = settingsRes ?? {};
            } else if (requestType === 'obsisstant.SetPreviewStream') {
                this.handleSetPreviewStream(normalizedData);
                return;
            } else if (requestType === 'obsisstant.ExecuteBatch') {
                const batch = Array.isArray(normalizedData.requests)
                    ? normalizedData.requests.map((step) => ({
                          requestType: step.requestType,
                          requestData: this.normalizeObsPayload(step.requestType, step.requestData),
                      }))
                    : [];
                responseData = await this.obs.callBatch(batch);
                await this.refreshAllState();
            } else if (requestType === 'obsisstant.RefreshAllState') {
                await this.refreshAllState();
                responseData = { refreshed: true };
            } else {
                responseData = (await this.obs.call(requestType, normalizedData)) ?? {};
                const incrementalEventCommands = new Set([
                    'SetSceneItemTransform',
                    'SetSceneItemEnabled',
                    'SetSceneItemLocked',
                    'SetInputMute',
                    'SetInputVolume',
                    'SetCurrentProgramScene',
                    'SetCurrentPreviewScene',
                    'SetStudioModeEnabled',
                ]);
                if (!incrementalEventCommands.has(requestType)) {
                    this.scheduleRefreshAllState(180);
                }
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
            const items = await this.hydrateSceneItems(sceneName);
            this.liveState.sceneItems = items;
            this.liveState.sceneItemsByScene[sceneName] = items;
            await this.pushState({
                currentProgramScene: sceneName,
                sceneItems: items,
                sceneItemsByScene: this.liveState.sceneItemsByScene,
            });
            if (this.previewConfig.active) {
                this.lastPreviewHash = null;
                this.captureAndUploadPreviewFrame(true);
            }
        });

        this.obs.on('CurrentPreviewSceneChanged', async ({ sceneName }) => {
            this.liveState.currentPreviewScene = sceneName;
            await this.pushState({ currentPreviewScene: sceneName });
        });

        this.obs.on('StudioModeStateChanged', async ({ studioModeEnabled }) => {
            this.liveState.studioModeEnabled = Boolean(studioModeEnabled);
            await this.pushState({ studioModeEnabled: this.liveState.studioModeEnabled });
        });

        this.obs.on('SceneListChanged', async () => {
            this.scheduleRefreshAllState(160);
        });

        this.obs.on('CurrentSceneCollectionChanged', () => {
            this.scheduleRefreshAllState(350);
        });

        this.obs.on('SceneCollectionListChanged', () => {
            this.scheduleRefreshAllState(250);
        });

        this.obs.on('SceneNameChanged', async () => {
            this.scheduleRefreshAllState(160);
        });

        this.obs.on('SceneItemCreated', () => this.scheduleRefreshAllState(160));
        this.obs.on('SceneItemRemoved', () => this.scheduleRefreshAllState(160));
        this.obs.on('SceneItemListReindexed', () => this.scheduleRefreshAllState(160));
        this.obs.on('SceneItemTransformChanged', ({ sceneName, sceneItemId, sceneItemTransform }) => {
            this.patchSceneItemTransform(sceneName, sceneItemId, sceneItemTransform);
        });
        this.obs.on('SceneItemLockStateChanged', async ({ sceneName, sceneItemId, sceneItemLocked }) => {
            const currentList = this.liveState.sceneItemsByScene[sceneName] || [];
            const updatedList = currentList.map((item) =>
                item.sceneItemId === sceneItemId ? { ...item, sceneItemLocked } : item
            );
            this.liveState.sceneItemsByScene[sceneName] = updatedList;

            const partial = { sceneItemsByScene: this.liveState.sceneItemsByScene };
            if (sceneName === this.liveState.currentProgramScene) {
                this.liveState.sceneItems = updatedList;
                partial.sceneItems = updatedList;
            }
            await this.pushState(partial);
        });
        this.obs.on('InputCreated', () => this.scheduleRefreshAllState(180));
        this.obs.on('InputRemoved', () => this.scheduleRefreshAllState(180));
        this.obs.on('InputNameChanged', () => this.scheduleRefreshAllState(180));
        this.obs.on('InputSettingsChanged', () => {
            this.lastPreviewHash = null;
            if (this.previewConfig.active) {
                this.captureAndUploadPreviewFrame(true);
            }
        });

        this.obs.on('SceneItemEnableStateChanged', async ({ sceneName, sceneItemId, sceneItemEnabled }) => {
            const currentList = this.liveState.sceneItemsByScene[sceneName] || [];
            const updatedList = currentList.map((item) =>
                item.sceneItemId === sceneItemId ? { ...item, sceneItemEnabled } : item
            );
            this.liveState.sceneItemsByScene[sceneName] = updatedList;

            const partial = { sceneItemsByScene: this.liveState.sceneItemsByScene };
            if (sceneName === this.liveState.currentProgramScene) {
                this.liveState.sceneItems = updatedList;
                partial.sceneItems = updatedList;
            }
            this.lastPreviewHash = null;
            await this.pushState(partial);
            if (this.previewConfig.active) {
                this.captureAndUploadPreviewFrame(true);
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

        this.studioChannel = pusherClient.subscribe(this.studioChannelName);
        this.studioChannel.bind('pusher:subscription_succeeded', () => {
            console.log(`${this.logPrefix()} WebSocket preview whisper stream ready on ${this.studioChannelName}`);
        });
        this.studioChannel.bind('client-obs.preview.subscribe', (payload) => {
            this.handleSetPreviewStream(payload);
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
                    const nextStats = {
                        activeFps: Number((stats.activeFps ?? 0).toFixed(1)),
                        cpuUsage: Number((stats.cpuUsage ?? 0).toFixed(1)),
                        memoryUsage: Number((stats.memoryUsage ?? 0).toFixed(1)),
                        renderSkippedFrames: stats.renderSkippedFrames ?? 0,
                        outputSkippedFrames: stats.outputSkippedFrames ?? 0,
                    };
                    this.liveState.stats = nextStats;

                    const sig = `${Math.round(nextStats.activeFps)}:${Math.round(nextStats.cpuUsage)}:${nextStats.renderSkippedFrames}:${nextStats.outputSkippedFrames}`;
                    if (sig !== this.lastStatsSignature) {
                        this.lastStatsSignature = sig;
                        await this.pushState({ stats: nextStats });
                    } else if (Date.now() - this.lastHeartbeatAt >= 24000) {
                        await this.pushHeartbeat('online');
                    }
                } catch {
                    // Ignore transient stat poll errors
                }
            } else if (Date.now() - this.lastHeartbeatAt >= 20000) {
                await this.pushHeartbeat('offline');
            }
        }, 12000);
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
        this.stopPreviewLoop();
        if (this.refreshDebounceTimer) {
            clearTimeout(this.refreshDebounceTimer);
            this.refreshDebounceTimer = null;
        }
        if (this.partialPushTimer) {
            clearTimeout(this.partialPushTimer);
            this.partialPushTimer = null;
        }
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
        if (this.studioChannel && pusherClient) {
            pusherClient.unsubscribe(this.studioChannelName);
            this.studioChannel = null;
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
        }, 300000);
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
