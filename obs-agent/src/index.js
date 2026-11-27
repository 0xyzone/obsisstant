import crypto from 'node:crypto';
import fs from 'node:fs';
import http from 'node:http';
import os from 'node:os';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import dotenv from 'dotenv';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import OBSWebSocket, { EventSubscription, RequestBatchExecutionType } from 'obs-websocket-js';

// Subscribe ONLY to low-volume OBS event categories actually handled by Obsisstant.
// Never subscribe to High-Volume events (like SceneItemTransformChanged) or unused categories.
const REQUIRED_EVENT_SUBSCRIPTIONS =
    EventSubscription.General |
    EventSubscription.Config |
    EventSubscription.Scenes |
    EventSubscription.Inputs |
    EventSubscription.Outputs |
    EventSubscription.SceneItems;

// Visual/structural OBS input kinds that never expose audio controls (avoids OBS error 604 spam).
const NON_AUDIO_INPUT_KINDS = new Set([
    'image_source',
    'color_source',
    'color_source_v2',
    'color_source_v3',
    'text_gdiplus',
    'text_gdiplus_v2',
    'text_gdiplus_v3',
    'text_ft2_source',
    'text_ft2_source_v2',
    'slideshow',
    'slideshow_v2',
    'scene',
    'group',
]);

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
const IS_RELAY_TOKEN = Boolean((process.env.OBSISSTANT_RELAY_TOKEN || '').trim());
const AGENT_INSTANCE_ID = (() => {
    const host = os.hostname() || 'agent';
    let userName = '';
    try {
        userName = os.userInfo().username || '';
    } catch {
        userName = '';
    }
    const tokenScope = crypto.createHash('sha256').update(AUTH_TOKEN).digest('hex').slice(0, 6);
    const fingerprint = crypto
        .createHash('sha256')
        .update(`${host}|${userName}|${os.homedir()}|${os.platform()}|${os.arch()}|${tokenScope}`)
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
const activeLocalEndpoints = new Map();

function normalizeEndpointKey(wsUrl) {
    try {
        const parsed = new URL(wsUrl);
        const rawHost = (parsed.hostname || '127.0.0.1').toLowerCase();
        const host = ['localhost', '::1', '[::1]', '0.0.0.0'].includes(rawHost) ? '127.0.0.1' : rawHost;
        const port = parsed.port || (parsed.protocol === 'wss:' ? '443' : '4455');
        return `${parsed.protocol}//${host}:${port}`;
    } catch {
        return String(wsUrl || 'ws://127.0.0.1:4455').toLowerCase();
    }
}

function wakeWorkersWaitingForEndpoint(endpointKey, releasedByStudioId) {
    if (!endpointKey) {
        return;
    }
    for (const worker of workers.values()) {
        if (worker.stopped || worker.studio.id === releasedByStudioId || worker.obsConnected || worker.connecting) {
            continue;
        }
        if (worker.waitingForEndpointKey === endpointKey && !worker.remoteAgentOnline) {
            worker.waitingForEndpointKey = null;
            worker.loggedEndpointStandby = false;
            if (worker.reconnectTimer) {
                clearTimeout(worker.reconnectTimer);
                worker.reconnectTimer = null;
            }
            worker.scheduleObsReconnect(250);
        }
    }
}

async function apiRequest(path, method = 'GET', body = null) {
    const headers = {
        Authorization: `Bearer ${AUTH_TOKEN}`,
        Accept: 'application/json',
        'X-Obs-Agent-Instance': AGENT_INSTANCE_ID,
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
        this.connecting = false;
        this.remoteAgentOnline = Boolean(entry.active_on_other_agent);
        this.claimedEndpointKey = null;
        this.waitingForEndpointKey = null;
        this.loggedEndpointStandby = false;
        this.loggedRemoteStandby = false;
        this.reconnectAttempts = 0;
        this.stopped = false;
        this.reconnectTimer = null;
        this.statsTimer = null;
        this.keepaliveTimer = null;
        this._boundPongHandler = null;
        this.wsPongAlive = true;
        this.refreshDebounceTimer = null;
        this.refreshInFlight = false;
        this.refreshQueued = false;
        this.lastFullRefreshAt = 0;
        this.partialPushTimer = null;
        this.volumePushTimer = null;
        this.previewTimer = null;
        this.previewInFlight = false;
        this.lastPreviewHash = null;
        this.lastPreviewUploadedAt = 0;
        this.lastPushedStats = null;
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

    resolveObsWsUrl() {
        return (
            (process.env.OBS_WS_URL || '').trim() ||
            `ws://${this.studio.obs_host || '127.0.0.1'}:${this.studio.obs_port || 4455}`
        );
    }

    releaseLocalEndpoint() {
        if (!this.claimedEndpointKey) {
            return;
        }
        const key = this.claimedEndpointKey;
        this.claimedEndpointKey = null;
        if (activeLocalEndpoints.get(key) === this.studio.id) {
            activeLocalEndpoints.delete(key);
            wakeWorkersWaitingForEndpoint(key, this.studio.id);
        }
    }

    startObsKeepalive() {
        this.stopObsKeepalive();
        const socket = this.obs?.socket;
        if (!socket || typeof socket.ping !== 'function') {
            return;
        }
        this.wsPongAlive = true;
        const onPong = () => {
            this.wsPongAlive = true;
        };
        this._boundPongHandler = onPong;
        if (typeof socket.on === 'function') {
            socket.on('pong', onPong);
        }
        // RFC 6455 transport ping keeps LAN/Wi-Fi sockets alive with 0 OBS RPC messages
        this.keepaliveTimer = setInterval(() => {
            if (this.stopped || !this.obsConnected || !this.obs?.socket) {
                this.stopObsKeepalive();
                return;
            }
            if (!this.wsPongAlive) {
                console.warn(`${this.logPrefix()} OBS WebSocket unresponsive to keepalive ping — resetting stale socket...`);
                try {
                    this.obs.socket.terminate?.();
                } catch {
                    this.obs.disconnect().catch(() => {});
                }
                return;
            }
            this.wsPongAlive = false;
            try {
                this.obs.socket.ping();
            } catch {
                // ConnectionClosed will handle dead sockets
            }
        }, 25000);
    }

    stopObsKeepalive() {
        if (this.keepaliveTimer) {
            clearInterval(this.keepaliveTimer);
            this.keepaliveTimer = null;
        }
        if (this._boundPongHandler && this.obs?.socket && typeof this.obs.socket.off === 'function') {
            try {
                this.obs.socket.off('pong', this._boundPongHandler);
            } catch {
                // Socket already closed
            }
        }
        this._boundPongHandler = null;
        this.wsPongAlive = true;
    }

    async yieldToRemoteOwner(reason = 'active on another PC') {
        this.remoteAgentOnline = true;
        this.stopObsKeepalive();
        if (!this.loggedRemoteStandby) {
            this.loggedRemoteStandby = true;
            console.log(`${this.logPrefix()} Standing by — studio is already ${reason}.`);
        }
        if (this.obsConnected) {
            this.obsConnected = false;
            this.releaseLocalEndpoint();
            await this.obs.disconnect().catch(() => {});
        } else {
            this.releaseLocalEndpoint();
        }
        this.scheduleObsReconnect(45000);
    }

    nextReconnectDelayMs(error = null) {
        const errCode = Number(error?.code || 0);
        const errMsg = String(error?.message || '').toLowerCase();
        const isAuthFailure = errCode === 4009 || errMsg.includes('authentication') || errMsg.includes('auth');
        this.reconnectAttempts = Math.min(this.reconnectAttempts + 1, 10);
        if (isAuthFailure) {
            return Math.min(120000, 30000 * this.reconnectAttempts);
        }
        return Math.min(20000, 5000 + Math.max(0, this.reconnectAttempts - 1) * 3000);
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
            if (res?.ignored_duplicate_relay || res?.ignored_duplicate_agent) {
                await this.yieldToRemoteOwner('owned by another active agent');
                return;
            }
            if (statusOverride === 'offline') {
                this.remoteAgentOnline = Boolean(res?.active_on_other_agent);
            } else {
                this.remoteAgentOnline = false;
                this.loggedRemoteStandby = false;
            }
            this.lastHeartbeatAt = Date.now();
        } catch (error) {
            console.error(`${this.logPrefix()} Failed to push state:`, error.message);
        }
    }

    async pushHeartbeat(status = 'offline') {
        if (this.stopped) {
            return null;
        }
        try {
            const res = await apiRequest('/api/obs-agent/heartbeat', 'POST', {
                studio_id: this.studio.id,
                agent_instance_id: AGENT_INSTANCE_ID,
                status,
                agent_pid: process.pid,
                agent_platform: os.platform(),
                agent_hostname: os.hostname(),
                agent_uptime: Math.floor(process.uptime()),
            });
            if (res?.ignored_duplicate_relay || res?.ignored_duplicate_agent) {
                await this.yieldToRemoteOwner('owned by another active agent');
                return res;
            }
            this.remoteAgentOnline = Boolean(res?.active_on_other_agent);
            if (!this.remoteAgentOnline) {
                this.loggedRemoteStandby = false;
            }
            this.lastHeartbeatAt = Date.now();
            return res;
        } catch {
            // Transient heartbeat error
            return null;
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

    extractBatchResult(results, index) {
        const entry = Array.isArray(results) ? results[index] : null;
        if (!entry || !entry.requestStatus?.result) {
            return null;
        }
        return entry.responseData ?? {};
    }

    async hydrateAllScenesItems(scenes) {
        const sceneNames = scenes.map((scene) => scene?.sceneName).filter(Boolean);
        if (!this.obsConnected || sceneNames.length === 0) {
            return {};
        }

        try {
            const results = await this.obs.callBatch(
                sceneNames.map((sceneName) => ({
                    requestType: 'GetSceneItemList',
                    requestData: { sceneName },
                })),
                {
                    executionType: RequestBatchExecutionType.SerialRealtime,
                    haltOnFailure: false,
                }
            );

            const byScene = {};
            sceneNames.forEach((sName, idx) => {
                const data = this.extractBatchResult(results, idx);
                const list = Array.isArray(data?.sceneItems) ? data.sceneItems : [];
                byScene[sName] = list.map((item) => ({
                    sceneItemId: item.sceneItemId,
                    sourceName: item.sourceName,
                    sourceType: item.sourceType ?? null,
                    inputKind: item.inputKind ?? null,
                    sceneItemIndex: item.sceneItemIndex ?? 0,
                    sceneItemEnabled: Boolean(item.sceneItemEnabled),
                    sceneItemLocked: Boolean(item.sceneItemLocked),
                    sceneItemTransform: item.sceneItemTransform ?? {},
                }));
            });

            return byScene;
        } catch {
            return {};
        }
    }

    async hydrateInputsAudio(inputs = []) {
        if (!this.obsConnected || !Array.isArray(inputs) || inputs.length === 0) {
            return [];
        }

        const candidates = inputs.filter((input) => {
            const kind = String(input?.inputKind || '').toLowerCase();
            return Boolean(input?.inputName) && !NON_AUDIO_INPUT_KINDS.has(kind);
        });

        if (candidates.length === 0) {
            return [];
        }

        try {
            const batch = [];
            for (const input of candidates) {
                batch.push({ requestType: 'GetInputMute', requestData: { inputName: input.inputName } });
                batch.push({ requestType: 'GetInputVolume', requestData: { inputName: input.inputName } });
            }

            const results = await this.obs.callBatch(batch, {
                executionType: RequestBatchExecutionType.SerialRealtime,
                haltOnFailure: false,
            });

            const audioInputs = [];
            for (let i = 0; i < candidates.length; i++) {
                const muteRes = this.extractBatchResult(results, i * 2);
                const volRes = this.extractBatchResult(results, i * 2 + 1);
                if (muteRes && volRes) {
                    audioInputs.push({
                        inputName: candidates[i].inputName,
                        inputKind: candidates[i].inputKind,
                        inputMuted: Boolean(muteRes.inputMuted),
                        inputVolumeMul: Number(volRes.inputVolumeMul ?? 1),
                        inputVolumeDb: Number(volRes.inputVolumeDb ?? 0),
                    });
                }
            }

            return audioInputs;
        } catch {
            return [];
        }
    }

    async refreshAllState(force = false) {
        if (!this.obsConnected || this.stopped) {
            return;
        }
        if (this.refreshInFlight) {
            this.refreshQueued = true;
            return;
        }

        const elapsedSinceLast = Date.now() - this.lastFullRefreshAt;
        if (!force && elapsedSinceLast < 2500) {
            this.scheduleRefreshAllState(2500 - elapsedSinceLast);
            return;
        }

        this.refreshInFlight = true;
        try {
            // Batch 1 (1 WS frame): Core OBS metadata, outputs, stats, and input list
            const coreResults = await this.obs.callBatch(
                [
                    { requestType: 'GetVersion' },
                    { requestType: 'GetSceneList' },
                    { requestType: 'GetSceneCollectionList' },
                    { requestType: 'GetStudioModeEnabled' },
                    { requestType: 'GetStreamStatus' },
                    { requestType: 'GetRecordStatus' },
                    { requestType: 'GetStats' },
                    { requestType: 'GetVideoSettings' },
                    { requestType: 'GetInputList' },
                    { requestType: 'GetInputKindList', requestData: { unversioned: false } },
                    { requestType: 'GetVirtualCamStatus' },
                    { requestType: 'GetReplayBufferStatus' },
                ],
                {
                    executionType: RequestBatchExecutionType.SerialRealtime,
                    haltOnFailure: false,
                }
            );

            const version = this.extractBatchResult(coreResults, 0) ?? {};
            const sceneList = this.extractBatchResult(coreResults, 1) ?? {};
            const collectionList = this.extractBatchResult(coreResults, 2) ?? {};
            const studioMode = this.extractBatchResult(coreResults, 3) ?? {};
            const streamStatus = this.extractBatchResult(coreResults, 4) ?? {};
            const recordStatus = this.extractBatchResult(coreResults, 5) ?? {};
            const stats = this.extractBatchResult(coreResults, 6) ?? {};
            const videoSettings = this.extractBatchResult(coreResults, 7);
            const inputListRes = this.extractBatchResult(coreResults, 8) ?? {};
            const kindsRes = this.extractBatchResult(coreResults, 9) ?? {};
            const vcRes = this.extractBatchResult(coreResults, 10);
            const rbRes = this.extractBatchResult(coreResults, 11);

            const virtualCam = Boolean(vcRes?.outputActive);
            const replayBuffer = Boolean(rbRes?.outputActive);
            const scenes = Array.isArray(sceneList.scenes) ? sceneList.scenes : [];
            const programScene = sceneList.currentProgramSceneName ?? null;
            const rawInputs = Array.isArray(inputListRes.inputs) ? inputListRes.inputs : [];
            const allInputs = rawInputs.map((input) => ({
                inputName: input.inputName,
                inputKind: input.inputKind,
            }));

            // Batch 2 & Batch 3 (at most 2 WS frames total)
            const sceneItemsByScene = await this.hydrateAllScenesItems(scenes);
            const audioInputs = await this.hydrateInputsAudio(rawInputs);

            const nextStats = {
                activeFps: Number((stats.activeFps ?? 0).toFixed(1)),
                cpuUsage: Number((stats.cpuUsage ?? 0).toFixed(1)),
                memoryUsage: Number((stats.memoryUsage ?? 0).toFixed(1)),
                renderSkippedFrames: stats.renderSkippedFrames ?? 0,
                outputSkippedFrames: stats.outputSkippedFrames ?? 0,
            };
            this.lastPushedStats = nextStats;

            this.liveState = {
                obsVersion: version.obsVersion ?? null,
                websocketVersion: version.obsWebSocketVersion ?? null,
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
                audioInputs,
                allInputs,
                inputKinds: Array.isArray(kindsRes?.inputKinds) ? kindsRes.inputKinds : [],
                outputs: {
                    streaming: Boolean(streamStatus.outputActive),
                    streamTimecode: streamStatus.outputTimecode ?? null,
                    recording: Boolean(recordStatus.outputActive),
                    recordingPaused: Boolean(recordStatus.outputPaused),
                    recordTimecode: recordStatus.outputTimecode ?? null,
                    virtualCam,
                    replayBuffer,
                },
                stats: nextStats,
                agentHost: {
                    platform: process.platform,
                    homeDir: os.homedir(),
                    defaultMediaDir: path.join(os.homedir(), 'Pictures', 'Obsisstant'),
                },
            };

            this.lastFullRefreshAt = Date.now();
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
                this.scheduleRefreshAllState(650);
            }
        }
    }

    scheduleRefreshAllState(delayMs = 650) {
        if (this.stopped || !this.obsConnected) {
            return;
        }
        if (this.refreshDebounceTimer) {
            clearTimeout(this.refreshDebounceTimer);
        }
        this.refreshDebounceTimer = setTimeout(() => {
            this.refreshDebounceTimer = null;
            this.refreshAllState(false);
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

        if (requestType === 'obsisstant.StopAgent') {
            await this.ackCommandWithRetry(uuid, {
                status: 'succeeded',
                response_data: { stopped: true, pid: process.pid },
            });
            console.log(`${this.logPrefix()} Received obsisstant.StopAgent — terminating agent cleanly...`);
            setTimeout(() => {
                shutdown().catch(() => process.exit(0));
            }, 200);
            return;
        }

        try {
            if (!this.obsConnected) {
                if (this.remoteAgentOnline || this.waitingForEndpointKey) {
                    if (requestType !== 'obsisstant.SetPreviewStream') {
                        console.log(`${this.logPrefix()} Skipping ${requestType} (${uuid || 'whisper'}) — studio is handled by another PC or endpoint.`);
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
                deferBackgroundSync = () => this.scheduleRefreshAllState(500);
            } else if (requestType === 'obsisstant.RefreshAllState') {
                await this.refreshAllState(true);
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
                    deferBackgroundSync = () => this.scheduleRefreshAllState(650);
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

        this.obs.on('SceneListChanged', () => {
            this.scheduleRefreshAllState(650);
        });

        this.obs.on('CurrentSceneCollectionChanged', () => {
            this.scheduleRefreshAllState(850);
        });

        this.obs.on('SceneCollectionListChanged', () => {
            this.scheduleRefreshAllState(800);
        });

        this.obs.on('SceneNameChanged', () => {
            this.scheduleRefreshAllState(650);
        });

        this.obs.on('SceneItemCreated', () => this.scheduleRefreshAllState(650));
        this.obs.on('SceneItemRemoved', () => this.scheduleRefreshAllState(650));
        this.obs.on('SceneItemListReindexed', () => this.scheduleRefreshAllState(650));
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
        this.obs.on('InputCreated', () => this.scheduleRefreshAllState(650));
        this.obs.on('InputRemoved', () => this.scheduleRefreshAllState(650));
        this.obs.on('InputNameChanged', () => this.scheduleRefreshAllState(650));
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

        this.obs.on('InputVolumeChanged', ({ inputName, inputVolumeMul, inputVolumeDb }) => {
            this.liveState.audioInputs = this.liveState.audioInputs.map((inp) =>
                inp.inputName === inputName ? { ...inp, inputVolumeMul, inputVolumeDb } : inp
            );
            if (this.volumePushTimer) {
                clearTimeout(this.volumePushTimer);
            }
            this.volumePushTimer = setTimeout(() => {
                this.volumePushTimer = null;
                if (!this.stopped && this.obsConnected) {
                    this.pushState({ audioInputs: this.liveState.audioInputs });
                }
            }, 400);
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

        this.obs.on('ConnectionClosed', (err) => {
            const wasConnected = this.obsConnected;
            this.obsConnected = false;
            this.stopObsKeepalive();
            this.releaseLocalEndpoint();
            if (this.stopped || !wasConnected) {
                return;
            }
            console.warn(`${this.logPrefix()} OBS WebSocket closed (${err?.code ?? 'closed'}). Scheduling reconnect...`);
            this.pushState({ ...this.liveState }, 'offline');
            this.scheduleObsReconnect(this.nextReconnectDelayMs(err));
        });
    }

    scheduleObsReconnect(delayMs = 5000) {
        if (this.stopped || this.reconnectTimer || this.obsConnected) {
            return;
        }
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.connectObs();
        }, delayMs);
    }

    async connectObs() {
        if (this.stopped || this.connecting || this.obsConnected) {
            return;
        }

        // Never dial OBS if this studio is already actively leased on another PC (both relay and single-studio modes)
        if (this.remoteAgentOnline) {
            const res = await this.pushHeartbeat('offline');
            if (res?.active_on_other_agent) {
                if (!this.loggedRemoteStandby) {
                    this.loggedRemoteStandby = true;
                    console.log(`${this.logPrefix()} Standing by — studio is active on another PC.`);
                }
                this.scheduleObsReconnect(45000);
                return;
            }
            this.remoteAgentOnline = false;
            this.loggedRemoteStandby = false;
        }

        const wsUrl = this.resolveObsWsUrl();
        const endpointKey = normalizeEndpointKey(wsUrl);
        const ownerStudioId = activeLocalEndpoints.get(endpointKey);
        if (ownerStudioId !== undefined && ownerStudioId !== this.studio.id) {
            this.waitingForEndpointKey = endpointKey;
            if (!this.loggedEndpointStandby) {
                this.loggedEndpointStandby = true;
                console.log(`${this.logPrefix()} Standing by — ${endpointKey} is already claimed on this PC by Studio #${ownerStudioId}.`);
            }
            this.scheduleObsReconnect(45000);
            return;
        }

        const password = (process.env.OBS_WS_PASSWORD || '').trim() || this.studio.obs_password || undefined;
        this.connecting = true;
        this.waitingForEndpointKey = null;
        this.loggedEndpointStandby = false;
        activeLocalEndpoints.set(endpointKey, this.studio.id);
        this.claimedEndpointKey = endpointKey;

        try {
            await this.obs.connect(wsUrl, password, {
                eventSubscriptions: REQUIRED_EVENT_SUBSCRIPTIONS,
                rpcVersion: 1,
            });
            this.obsConnected = true;
            this.remoteAgentOnline = false;
            this.loggedRemoteStandby = false;
            this.reconnectAttempts = 0;
            this.startObsKeepalive();
            console.log(`${this.logPrefix()} Connected to OBS Studio at ${wsUrl}`);
        } catch (error) {
            this.obsConnected = false;
            this.stopObsKeepalive();
            this.releaseLocalEndpoint();
            const delayMs = this.nextReconnectDelayMs(error);
            if (this.reconnectAttempts <= 2 || this.reconnectAttempts % 5 === 0) {
                console.warn(`${this.logPrefix()} Waiting for OBS at ${wsUrl}: ${error.message} (retry in ${Math.round(delayMs / 1000)}s)`);
            }
            await this.pushHeartbeat('offline');
            this.scheduleObsReconnect(this.remoteAgentOnline ? 45000 : delayMs);
            return;
        } finally {
            this.connecting = false;
        }

        if (this.obsConnected && !this.stopped) {
            await this.refreshAllState(true);
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

        this.lastIdleStatsPollAt = 0;
        this.statsTimer = setInterval(async () => {
            if (this.stopped) {
                return;
            }
            if (this.obsConnected) {
                const isLive = Boolean(this.liveState?.outputs?.streaming || this.liveState?.outputs?.recording);
                const minInterval = isLive ? 25000 : 60000;
                if (Date.now() - this.lastIdleStatsPollAt < minInterval) {
                    return;
                }
                this.lastIdleStatsPollAt = Date.now();
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

                    const prev = this.lastPushedStats;
                    const significantDelta = !prev
                        || Math.abs(nextStats.activeFps - prev.activeFps) >= 2
                        || Math.abs(nextStats.cpuUsage - prev.cpuUsage) >= 5
                        || nextStats.renderSkippedFrames !== prev.renderSkippedFrames
                        || nextStats.outputSkippedFrames !== prev.outputSkippedFrames;

                    if (significantDelta) {
                        this.lastPushedStats = nextStats;
                        await this.pushState({ stats: nextStats });
                    } else if (Date.now() - this.lastHeartbeatAt >= 45000) {
                        await this.pushHeartbeat('online');
                    }
                } catch {
                    // Ignore transient stat poll errors
                }
            } else if (!this.waitingForEndpointKey && !this.remoteAgentOnline && Date.now() - this.lastHeartbeatAt >= 45000) {
                await this.pushHeartbeat('offline');
            }
        }, 30000);
    }

    async updateConfig(entry) {
        const nextSignature = this.buildSignature(entry.studio);
        const oldName = this.studio.name;
        this.studio = entry.studio;

        if (!this.obsConnected && typeof entry.active_on_other_agent === 'boolean') {
            this.remoteAgentOnline = entry.active_on_other_agent;
            if (!this.remoteAgentOnline) {
                this.loggedRemoteStandby = false;
            }
        }

        if (nextSignature !== this.signature) {
            this.signature = nextSignature;
            this.reconnectAttempts = 0;
            this.loggedEndpointStandby = false;
            console.log(`${this.logPrefix()} Connection profile updated (${oldName} -> ${this.studio.name}). Reconnecting OBS...`);
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }
            this.stopObsKeepalive();
            if (this.obsConnected) {
                this.obsConnected = false;
                this.releaseLocalEndpoint();
                await this.obs.disconnect().catch(() => {});
            } else {
                this.releaseLocalEndpoint();
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
        this.stopObsKeepalive();
        this.releaseLocalEndpoint();
        if (this.refreshDebounceTimer) {
            clearTimeout(this.refreshDebounceTimer);
            this.refreshDebounceTimer = null;
        }
        if (this.partialPushTimer) {
            clearTimeout(this.partialPushTimer);
            this.partialPushTimer = null;
        }
        if (this.volumePushTimer) {
            clearTimeout(this.volumePushTimer);
            this.volumePushTimer = null;
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
        activityTimeout: 25000,
        pongTimeout: 10000,
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

    const delayMs = pusherConnected ? 30000 : 3000;
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

const LOCK_FILE = path.join(os.tmpdir(), 'obsisstant-agent.lock');
let localUiServer = null;

async function acquireSingleInstanceLock() {
    try {
        if (fs.existsSync(LOCK_FILE)) {
            const rawPid = fs.readFileSync(LOCK_FILE, 'utf8').trim();
            const oldPid = Number.parseInt(rawPid, 10);
            if (Number.isFinite(oldPid) && oldPid > 0 && oldPid !== process.pid) {
                try {
                    process.kill(oldPid, 0);
                    console.log(`[obs-agent] Replacing previous agent instance (PID ${oldPid})...`);
                    process.kill(oldPid, 'SIGTERM');
                    await new Promise((resolve) => setTimeout(resolve, 350));
                } catch {
                    // Previous PID already exited
                }
            }
        }
        fs.writeFileSync(LOCK_FILE, String(process.pid), 'utf8');
    } catch {
        // Non-fatal if lockfile cannot be written
    }
}

function releaseSingleInstanceLock() {
    try {
        if (fs.existsSync(LOCK_FILE)) {
            const rawPid = fs.readFileSync(LOCK_FILE, 'utf8').trim();
            if (Number.parseInt(rawPid, 10) === process.pid) {
                fs.unlinkSync(LOCK_FILE);
            }
        }
    } catch {
        // Ignore cleanup errors
    }
}

function startLocalCompanionServer() {
    const port = Number.parseInt(process.env.OBS_AGENT_UI_PORT || '4458', 10);
    if (!Number.isFinite(port) || port <= 0) {
        return;
    }

    localUiServer = http.createServer(async (req, res) => {
        const url = new URL(req.url || '/', `http://127.0.0.1:${port}`);
        res.setHeader('Content-Type', 'application/json; charset=utf-8');

        if (req.method === 'GET' && url.pathname === '/status') {
            const studios = Array.from(workers.values()).map((w) => ({
                id: w.studio.id,
                name: w.studio.name,
                obsConnected: w.obsConnected,
                status: w.deriveStatus(),
            }));
            res.end(JSON.stringify({
                running: true,
                pid: process.pid,
                platform: os.platform(),
                hostname: os.hostname(),
                uptime: Math.floor(process.uptime()),
                baseUrl: BASE_URL,
                studios,
            }));
            return;
        }

        if (req.method === 'POST' && url.pathname === '/stop') {
            res.end(JSON.stringify({ ok: true, message: 'Stopping Obsisstant Agent...' }));
            setTimeout(() => shutdown().catch(() => process.exit(0)), 150);
            return;
        }

        if (req.method === 'POST' && url.pathname === '/pair') {
            let body = '';
            req.on('data', (chunk) => { body += chunk; });
            req.on('end', async () => {
                try {
                    const parsed = JSON.parse(body || '{}');
                    const code = String(parsed.code || '').replace(/\D/g, '');
                    const serverBase = String(parsed.baseUrl || BASE_URL).replace(/\/+$/, '');
                    if (code.length !== 6) {
                        res.statusCode = 422;
                        res.end(JSON.stringify({ ok: false, message: 'Enter a valid 6-digit pairing code.' }));
                        return;
                    }
                    const pairRes = await fetch(`${serverBase}/api/obs-agent/pair-code`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                        body: JSON.stringify({ code }),
                    });
                    const pairJson = await pairRes.json().catch(() => null);
                    if (!pairRes.ok || !pairJson?.relay_token) {
                        res.statusCode = pairRes.status || 400;
                        res.end(JSON.stringify({ ok: false, message: pairJson?.message || 'Pairing code invalid or expired.' }));
                        return;
                    }
                    const envPath = path.resolve(__dirname, '../.env');
                    const nextEnv = [
                        `OBSISSTANT_URL=${pairJson.api_base || serverBase}`,
                        `OBS_RELAY_TOKEN=${pairJson.relay_token}`,
                        `OBS_DEFAULT_HOST=${pairJson.obs_host || '127.0.0.1'}`,
                        `OBS_DEFAULT_PORT=${pairJson.obs_port || 4455}`,
                        `OBS_DEFAULT_PASSWORD=${pairJson.obs_password || ''}`,
                    ].join('\n') + '\n';
                    fs.writeFileSync(envPath, nextEnv, 'utf8');
                    res.end(JSON.stringify({ ok: true, message: 'Paired! Restarting agent connection...' }));
                    setTimeout(() => process.exit(0), 300);
                } catch (err) {
                    res.statusCode = 500;
                    res.end(JSON.stringify({ ok: false, message: err.message }));
                }
            });
            return;
        }

        if (req.method === 'GET' && url.pathname === '/') {
            res.setHeader('Content-Type', 'text/html; charset=utf-8');
            res.end(`<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Obsisstant Agent</title><style>body{margin:0;font-family:system-ui,-apple-system,sans-serif;background:#070706;color:#FAF7EE;display:grid;place-items:center;min-height:100vh;padding:16px}main{width:100%;max-width:420px;background:#121210;border:1px solid rgba(205,173,105,.38);border-radius:16px;padding:20px;display:grid;gap:14px}h1{margin:0;font-size:18px;color:#F4E185}p{margin:0;font-size:13px;color:#C8C0AF}input{width:100%;box-sizing:border-box;padding:10px 12px;border-radius:10px;border:1px solid rgba(205,173,105,.35);background:#080807;color:#FAF7EE;font-size:16px;letter-spacing:4px;text-align:center}button{padding:10px 14px;border-radius:999px;border:0;font-weight:600;cursor:pointer}.gold{background:linear-gradient(180deg,#F6E496,#B89143);color:#070706}.danger{background:#2D1314;color:#F4A3A6;border:1px solid rgba(229,72,77,.45)}.row{display:flex;gap:8px;justify-content:space-between;align-items:center}</style></head><body><main><div class="row"><h1>Obsisstant Agent</h1><span style="font-size:12px;color:#9EE6C3">PID ${process.pid}</span></div><p id="st">Loading status...</p><form id="pf" style="display:grid;gap:8px"><input id="code" maxlength="7" placeholder="482-915" required><button class="gold" type="submit">Pair with 6-Digit Code</button></form><button id="stop" class="danger" type="button">Stop Agent</button></main><script>fetch('/status').then(r=>r.json()).then(d=>{document.getElementById('st').textContent='Running on '+d.hostname+' ('+d.platform+') · '+d.studios.length+' studio(s)';});document.getElementById('pf').onsubmit=async e=>{e.preventDefault();const r=await fetch('/pair',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({code:document.getElementById('code').value})});const j=await r.json();alert(j.message);if(j.ok)location.reload();};document.getElementById('stop').onclick=async()=>{await fetch('/stop',{method:'POST'});document.getElementById('st').textContent='Agent stopped.';};</script></body></html>`);
            return;
        }

        res.statusCode = 404;
        res.end(JSON.stringify({ message: 'Not found' }));
    });

    localUiServer.on('error', () => {
        localUiServer = null;
    });
    localUiServer.listen(port, '127.0.0.1', () => {
        console.log(`[obs-agent] Local status & pairing UI: http://127.0.0.1:${port}`);
    });
}

async function main() {
    await acquireSingleInstanceLock();
    startLocalCompanionServer();
    console.log(`[obs-agent] Bootstrapping from ${BASE_URL}...`);

    let bootstrap = null;
    while (!bootstrap) {
        try {
            bootstrap = await syncFromBootstrap();
        } catch (err) {
            console.error(`[obs-agent] Bootstrap failed (${err.message}). Keeping local UI alive at http://127.0.0.1:4458 and retrying in 10s...`);
            await new Promise((resolve) => setTimeout(resolve, 10000));
        }
    }

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
    if (localUiServer) {
        try {
            localUiServer.close();
        } catch {
            // Ignore close errors
        }
    }
    releaseSingleInstanceLock();
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);

main().catch((err) => {
    console.error('[obs-agent] Fatal startup error:', err.message);
    process.exit(1);
});
