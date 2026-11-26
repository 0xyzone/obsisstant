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
const AGENT_INSTANCE_ID = (() => {
    const host = os.hostname() || 'agent';
    let userName = '';
    try {
        userName = os.userInfo().username || '';
    } catch {
        userName = '';
    }
    const fingerprint = crypto
        .createHash('sha256')
        .update(`${host}|${userName}|${os.homedir()}|${os.platform()}|${os.arch()}`)
        .digest('hex')
        .slice(0, 10);
    return `${host}-${fingerprint}`.slice(0, 120);
})();

if (!BASE_URL || !AUTH_TOKEN) {
    console.error('[obs-agent] OBSISSTANT_URL and OBSISSTANT_RELAY_TOKEN (or OBSISSTANT_AGENT_TOKEN) are required in obs-agent/.env');
    process.exit(1);
}

let pusherClient = null;
let pusherConnected = false;
let relayChannelSub = null;
let catalogPollTimer = null;
let pendingPollTimer = null;
let pendingPollInFlight = false;
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
        this.remoteAgentOnline = false;
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
        this.handledCommandUuids = new Set();
        this.lastCommandSceneSync = { sceneName: null, at: 0 };
        this.liveState = emptyLiveState();
        this.signature = this.buildSignature(this.studio);
        this.bindObsEvents();
    }

    rememberCommand(uuid) {
        if (!uuid) {
            return false;
        }
        if (this.handledCommandUuids.has(uuid)) {
            return true;
        }
        this.handledCommandUuids.add(uuid);
        if (this.handledCommandUuids.size > 500) {
            const oldest = this.handledCommandUuids.values().next().value;
            this.handledCommandUuids.delete(oldest);
        }
        return false;
    }

    async drainPendingCommands(commands = []) {
        if (!Array.isArray(commands) || commands.length === 0 || this.stopped) {
            return;
        }
        for (const pending of commands) {
            await this.executeCommand(pending);
        }
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
                agent_instance_id: AGENT_INSTANCE_ID,
                live_state: partial ?? this.liveState,
            };
            if (statusOverride) {
                payload.status = statusOverride;
            }
            const res = await apiRequest('/api/obs-agent/state', 'POST', payload);
            if (statusOverride === 'offline') {
                this.remoteAgentOnline = Boolean(res?.active_on_other_agent);
            } else {
                this.remoteAgentOnline = false;
            }
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
            const res = await apiRequest('/api/obs-agent/heartbeat', 'POST', {
                studio_id: this.studio.id,
                agent_instance_id: AGENT_INSTANCE_ID,
                status,
            });
            this.remoteAgentOnline = Boolean(res?.active_on_other_agent);
            this.lastHeartbeatAt = Date.now();
        } catch {
            // Transient heartbeat error
        }
    }

    transformsEqual(current = {}, incoming = {}) {
        const keys = ['positionX', 'positionY', 'scaleX', 'scaleY', 'rotation', 'width', 'height', 'boundsWidth', 'boundsHeight', 'boundsType', 'alignment', 'cropTop', 'cropBottom', 'cropLeft', 'cropRight'];
        return keys.every((key) => {
            if (incoming[key] === undefined) {
                return true;
            }
            return current[key] === incoming[key];
        });
    }

    updateLocalSceneItem(sceneName, sceneItemId, updater) {
        const list = this.liveState.sceneItemsByScene?.[sceneName];
        if (!Array.isArray(list)) {
            return null;
        }

        let matched = false;
        const updated = list.map((item) => {
            if (Number(item.sceneItemId) !== Number(sceneItemId)) {
                return item;
            }
            matched = true;
            return updater(item);
        });

        if (!matched) {
            return null;
        }

        this.liveState.sceneItemsByScene[sceneName] = updated;
        if (sceneName === this.liveState.currentProgramScene) {
            this.liveState.sceneItems = updated;
        }

        return updated;
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

    async flushSceneItemsPush(sceneName, capturePreview = false) {
        if (this.partialPushTimer) {
            clearTimeout(this.partialPushTimer);
            this.partialPushTimer = null;
        }

        const partial = {
            sceneItemsByScene: {
                ...this.liveState.sceneItemsByScene,
            },
        };
        if (sceneName === this.liveState.currentProgramScene) {
            partial.sceneItems = this.liveState.sceneItemsByScene[sceneName] ?? [];
        }

        if (capturePreview) {
            this.lastPreviewHash = null;
        }

        await this.pushState(partial);

        if (capturePreview && this.previewConfig.active) {
            this.captureAndUploadPreviewFrame(true);
        }
    }

    patchSceneItemTransform(sceneName, sceneItemId, sceneItemTransform) {
        const list = this.liveState.sceneItemsByScene?.[sceneName];
        if (!Array.isArray(list)) {
            this.scheduleRefreshAllState(200);
            return;
        }

        const existing = list.find((item) => Number(item.sceneItemId) === Number(sceneItemId));
        if (!existing) {
            this.scheduleRefreshAllState(200);
            return;
        }

        if (this.transformsEqual(existing.sceneItemTransform || {}, sceneItemTransform || {})) {
            return;
        }

        this.updateLocalSceneItem(sceneName, sceneItemId, (item) => ({
            ...item,
            sceneItemTransform: {
                ...(item.sceneItemTransform || {}),
                ...(sceneItemTransform || {}),
            },
        }));

        this.schedulePartialScenePush(sceneName);
    }

    async syncIncrementalCommandState(requestType, requestData = {}) {
        const sceneName = requestData.sceneName || this.liveState.currentProgramScene;

        if (requestType === 'SetSceneItemTransform' && sceneName && requestData.sceneItemId !== undefined) {
            try {
                const { sceneItemTransform } = await this.obs.call('GetSceneItemTransform', {
                    sceneName,
                    sceneItemId: Number(requestData.sceneItemId),
                });
                const updated = this.updateLocalSceneItem(sceneName, requestData.sceneItemId, (item) => ({
                    ...item,
                    sceneItemTransform: sceneItemTransform || {
                        ...(item.sceneItemTransform || {}),
                        ...(requestData.sceneItemTransform || {}),
                    },
                }));
                if (updated) {
                    await this.flushSceneItemsPush(sceneName, true);
                    return;
                }
            } catch {
                // Fall back to full refresh when single-item query fails
            }
            await this.refreshAllState();
            return;
        }

        if (requestType === 'SetSceneItemEnabled' && sceneName && requestData.sceneItemId !== undefined) {
            const enabled = Boolean(requestData.sceneItemEnabled);
            const updated = this.updateLocalSceneItem(sceneName, requestData.sceneItemId, (item) => ({
                ...item,
                sceneItemEnabled: enabled,
            }));
            if (updated) {
                await this.flushSceneItemsPush(sceneName, true);
                return;
            }
            await this.refreshAllState();
            return;
        }

        if (requestType === 'SetSceneItemLocked' && sceneName && requestData.sceneItemId !== undefined) {
            const locked = Boolean(requestData.sceneItemLocked);
            const updated = this.updateLocalSceneItem(sceneName, requestData.sceneItemId, (item) => ({
                ...item,
                sceneItemLocked: locked,
            }));
            if (updated) {
                await this.flushSceneItemsPush(sceneName, false);
                return;
            }
            await this.refreshAllState();
            return;
        }

        if (requestType === 'SetCurrentProgramScene' && requestData.sceneName) {
            const items = await this.hydrateSceneItems(requestData.sceneName);
            this.liveState.currentProgramScene = requestData.sceneName;
            this.liveState.sceneItems = items;
            this.liveState.sceneItemsByScene[requestData.sceneName] = items;
            this.lastPreviewHash = null;
            await this.pushState({
                currentProgramScene: requestData.sceneName,
                sceneItems: items,
            });
            if (this.previewConfig.active) {
                this.captureAndUploadPreviewFrame(true);
            }
            return;
        }

        if (requestType === 'SetCurrentPreviewScene' && requestData.sceneName) {
            this.liveState.currentPreviewScene = requestData.sceneName;
            await this.pushState({ currentPreviewScene: requestData.sceneName });
            return;
        }

        if (requestType === 'SetStudioModeEnabled') {
            this.liveState.studioModeEnabled = Boolean(requestData.studioModeEnabled);
            await this.pushState({ studioModeEnabled: this.liveState.studioModeEnabled });
            return;
        }

        if (requestType === 'SetInputMute' && requestData.inputName) {
            this.liveState.audioInputs = this.liveState.audioInputs.map((inp) =>
                inp.inputName === requestData.inputName ? { ...inp, inputMuted: Boolean(requestData.inputMuted) } : inp
            );
            await this.pushState({ audioInputs: this.liveState.audioInputs });
            return;
        }

        if (requestType === 'SetInputVolume' && requestData.inputName) {
            try {
                const vol = await this.obs.call('GetInputVolume', { inputName: requestData.inputName });
                this.liveState.audioInputs = this.liveState.audioInputs.map((inp) =>
                    inp.inputName === requestData.inputName
                        ? {
                              ...inp,
                              inputVolumeMul: Number(vol.inputVolumeMul ?? inp.inputVolumeMul ?? 1),
                              inputVolumeDb: Number(vol.inputVolumeDb ?? inp.inputVolumeDb ?? 0),
                          }
                        : inp
                );
                await this.pushState({ audioInputs: this.liveState.audioInputs });
            } catch {
                // Event handler updates if direct query fails
            }
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
        const { sceneName, inputName, url, width = 1920, height = 1080, rerouteAudio = false } = data;
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
            reroute_audio: Boolean(rerouteAudio),
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

    deriveStatus() {
        if (!this.obsConnected) {
            return 'offline';
        }
        if (this.liveState?.outputs?.streaming) {
            return 'streaming';
        }
        if (this.liveState?.outputs?.recording) {
            return 'recording';
        }
        return 'online';
    }

    emitWhisper(eventName, payload) {
        if (!this.studioChannel?.subscribed) {
            return false;
        }
        try {
            return Boolean(this.studioChannel.trigger(eventName, payload));
        } catch {
            return false;
        }
    }

    async ackCommandWithRetry(uuid, body) {
        if (!uuid) {
            return;
        }
        const retryDelays = [0, 350, 750, 1400];
        const ackPayload = {
            ...body,
            agent_instance_id: AGENT_INSTANCE_ID,
        };
        for (let attempt = 0; attempt < retryDelays.length; attempt++) {
            if (retryDelays[attempt] > 0) {
                await new Promise((resolve) => setTimeout(resolve, retryDelays[attempt]));
            }
            try {
                await apiRequest(`/api/obs-agent/commands/${uuid}/ack`, 'POST', ackPayload);
                return;
            } catch (error) {
                const isNotFound = String(error?.message || '').includes('(404)');
                if (!isNotFound || attempt === retryDelays.length - 1) {
                    console.error(`${this.logPrefix()} Ack failed:`, error.message);
                    return;
                }
            }
        }
    }

    async executeCommand(payload) {
        const { command_uuid: uuid, request_type: requestType, request_data: requestData = {} } = payload || {};
        if (!requestType) {
            return;
        }
        if (requestType !== 'obsisstant.SetPreviewStream' && this.rememberCommand(uuid)) {
            return;
        }
        const normalizedData = this.normalizeObsPayload(requestType, requestData);
        if (requestType !== 'obsisstant.SetPreviewStream') {
            console.log(`${this.logPrefix()} Executing ${requestType} (${uuid || 'whisper'})`);
        }

        try {
            if (!this.obsConnected) {
                if (this.remoteAgentOnline) {
                    if (requestType !== 'obsisstant.SetPreviewStream') {
                        console.log(`${this.logPrefix()} Skipping ${requestType} (${uuid || 'whisper'}) — studio is active on another PC.`);
                    }
                    return;
                }
                throw new Error('OBS Studio WebSocket is not currently connected.');
            }

            let responseData = {};
            let deferBackgroundSync = null;

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
                deferBackgroundSync = () => this.refreshAllState();
            } else if (requestType === 'obsisstant.RefreshAllState') {
                await this.refreshAllState();
                responseData = { refreshed: true };
            } else {
                responseData = (await this.obs.call(requestType, normalizedData)) ?? {};

                if (requestType === 'SetCurrentProgramScene' && normalizedData.sceneName) {
                    const nextScene = normalizedData.sceneName;
                    const cachedItems = this.liveState.sceneItemsByScene?.[nextScene];
                    this.liveState.currentProgramScene = nextScene;
                    if (Array.isArray(cachedItems)) {
                        this.liveState.sceneItems = cachedItems;
                    }
                    this.lastCommandSceneSync = { sceneName: nextScene, at: Date.now() };
                    this.emitWhisper('client-obs.studio.state', {
                        studio_id: this.studio.id,
                        slug: this.studio.slug,
                        status: this.deriveStatus(),
                        is_online: true,
                        live_state: {
                            currentProgramScene: nextScene,
                            ...(Array.isArray(cachedItems) ? { sceneItems: cachedItems } : {}),
                        },
                    });
                }

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
                if (incrementalEventCommands.has(requestType)) {
                    deferBackgroundSync = () => this.syncIncrementalCommandState(requestType, normalizedData);
                } else {
                    deferBackgroundSync = () => this.scheduleRefreshAllState(180);
                }
            }

            const executedAt = new Date().toISOString();
            this.emitWhisper('client-obs.command.acknowledged', {
                command_uuid: uuid,
                studio_id: this.studio.id,
                request_type: requestType,
                status: 'succeeded',
                has_response_data: Boolean(responseData && Object.keys(responseData).length > 0),
                error_message: null,
                executed_at: executedAt,
            });

            if (deferBackgroundSync) {
                void Promise.resolve(deferBackgroundSync()).catch(() => {});
                void this.ackCommandWithRetry(uuid, {
                    studio_id: this.studio.id,
                    status: 'succeeded',
                    response_data: responseData,
                });
            } else {
                await this.ackCommandWithRetry(uuid, {
                    studio_id: this.studio.id,
                    status: 'succeeded',
                    response_data: responseData,
                });
            }
        } catch (error) {
            console.error(`${this.logPrefix()} Command failed (${requestType}):`, error.message);
            this.emitWhisper('client-obs.command.acknowledged', {
                command_uuid: uuid,
                studio_id: this.studio.id,
                request_type: requestType,
                status: 'failed',
                error_message: error.message,
                executed_at: new Date().toISOString(),
            });
            await this.ackCommandWithRetry(uuid, {
                studio_id: this.studio.id,
                status: 'failed',
                error_message: error.message,
            });
        }
    }

    bindObsEvents() {
        this.obs.on('CurrentProgramSceneChanged', async ({ sceneName }) => {
            if (this.lastCommandSceneSync?.sceneName === sceneName && Date.now() - this.lastCommandSceneSync.at < 1500) {
                return;
            }
            this.liveState.currentProgramScene = sceneName;
            const items = await this.hydrateSceneItems(sceneName);
            this.liveState.sceneItems = items;
            this.liveState.sceneItemsByScene[sceneName] = items;
            this.emitWhisper('client-obs.studio.state', {
                studio_id: this.studio.id,
                slug: this.studio.slug,
                status: this.deriveStatus(),
                is_online: true,
                live_state: {
                    currentProgramScene: sceneName,
                    sceneItems: items,
                },
            });
            await this.pushState({
                currentProgramScene: sceneName,
                sceneItems: items,
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
            const existing = currentList.find((item) => Number(item.sceneItemId) === Number(sceneItemId));
            if (existing && Boolean(existing.sceneItemLocked) === Boolean(sceneItemLocked)) {
                return;
            }
            if (this.updateLocalSceneItem(sceneName, sceneItemId, (item) => ({ ...item, sceneItemLocked }))) {
                await this.flushSceneItemsPush(sceneName, false);
            }
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
            const existing = currentList.find((item) => Number(item.sceneItemId) === Number(sceneItemId));
            if (existing && Boolean(existing.sceneItemEnabled) === Boolean(sceneItemEnabled)) {
                return;
            }
            if (this.updateLocalSceneItem(sceneName, sceneItemId, (item) => ({ ...item, sceneItemEnabled }))) {
                await this.flushSceneItemsPush(sceneName, true);
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
                eventSubscriptions: EventSubscription.All | EventSubscription.SceneItemTransformChanged,
                rpcVersion: 1,
            });
            this.obsConnected = true;
            this.remoteAgentOnline = false;
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
        this.studioChannel.bind('client-obs.command', (payload) => {
            this.executeCommand(payload);
        });
    }

    async start() {
        this.subscribeCommandChannel();
        await this.connectObs();

        await this.drainPendingCommands(this.pendingCommands);
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

        if (Array.isArray(entry.pending_commands) && entry.pending_commands.length > 0) {
            await this.drainPendingCommands(entry.pending_commands);
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

    // Force pusher-js Node runtime to use the standard `ws` package instead of
    // its bundled legacy faye-websocket client (which fails TLS/proxy handshakes).
    if (Pusher.Runtime) {
        Pusher.Runtime.getWebSocketAPI = () => WebSocket;
        Pusher.Runtime.createWebSocket = (url) => new WebSocket(url);
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

    pusherClient.connection.bind('state_change', ({ previous, current }) => {
        pusherConnected = current === 'connected';
        if (current === 'connected') {
            console.log(`[obs-agent] Reverb connected (${reverbConfig.scheme}://${reverbConfig.host}:${reverbConfig.port})`);
            pollPendingCommands().catch(() => {});
        } else if (previous === 'connected') {
            console.warn(`[obs-agent] Reverb ${current} — polling pending command queue until restored.`);
        }
        schedulePendingPoll();
    });

    pusherClient.connection.bind('error', (err) => {
        const msg = err?.error?.data?.message || err?.data?.message || err?.message || 'connection error';
        console.warn(`[obs-agent] Reverb socket error: ${msg}`);
    });
}

async function pollPendingCommands() {
    if (pendingPollInFlight || workers.size === 0) {
        return;
    }

    pendingPollInFlight = true;
    try {
        const payload = await apiRequest('/api/obs-agent/commands/pending');
        const entries = Array.isArray(payload?.studios) ? payload.studios : [];
        for (const entry of entries) {
            const worker = workers.get(entry.studio_id);
            if (worker && Array.isArray(entry.pending_commands) && entry.pending_commands.length > 0) {
                await worker.drainPendingCommands(entry.pending_commands);
            }
        }
    } catch (error) {
        if (String(error?.message || '').includes('(404)')) {
            await syncFromBootstrap().catch(() => {});
        }
    } finally {
        pendingPollInFlight = false;
    }
}

function schedulePendingPoll() {
    if (pendingPollTimer) {
        clearTimeout(pendingPollTimer);
        pendingPollTimer = null;
    }

    const delayMs = pusherConnected ? 15000 : 2000;
    pendingPollTimer = setTimeout(async () => {
        pendingPollTimer = null;
        await pollPendingCommands().catch(() => {});
        schedulePendingPoll();
    }, delayMs);
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

    schedulePendingPoll();
}

async function shutdown() {
    clearInterval(catalogPollTimer);
    if (pendingPollTimer) {
        clearTimeout(pendingPollTimer);
        pendingPollTimer = null;
    }
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
