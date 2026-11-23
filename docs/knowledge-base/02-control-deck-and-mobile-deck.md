---
title: Studio Control Center & Mobile Deck
slug: control-deck
category: Core Features
order: 2
summary: Direct scenes (create/rename/delete), build live 16:9 source layouts, control live pixel/WebRTC previews, mix audio, and cue from mobile.
---

## Studio Control Center (`/studio`)

For full broadcast production, launch the standalone **Studio Control Center** (`/studio` or `/studio/{studio-slug}`), reachable from the landing page, Admin Dashboard, and Filament sidebar. Unlike administrative tables, `/studio` renders a three-column dark workspace driven over Reverb WebSockets.

### 1. Full Scene & Scene Collection Management

- **Scene Collections**: Switch active Scene Collections (`SetCurrentSceneCollection`) or create new collections (`CreateSceneCollection`) directly from the top of the left Scenes dock. *(Note: OBS WebSocket v5 does not expose rename/delete requests for Scene Collections—manage those inside the OBS Desktop Scene Collection menu.)*
- **Select Scene for Staging vs Live Cut**: Click any scene row to inspect and rearrange its sources on the center stage without changing what your audience sees live. Click **Live** (or **Preview** when Studio Mode is active) to switch OBS output.
- **Create, Rename & Delete Scenes**: Add scenes inline (`CreateScene`), rename existing scenes (`SetSceneName`), or delete scenes (`RemoveScene`) directly inside OBS Studio.

### 2. Interactive 16:9 Live View & Source Stage

- **Three Live View Modes**:
  - **Layout Grid Only**: Zero-overhead vector layout canvas synced with `videoDimensions` and live `sceneItemTransform`.
  - **OBS Pixel Feed (Snapshots)**: On-demand `GetSourceScreenshot` snapshots uploaded by `obs-agent` at ~1.5 FPS behind interactive source frames.
  - **Local OBS Virtual Cam (WebRTC)**: Connects directly to your workstation's camera or OBS Virtual Camera via browser `getUserMedia()` for smooth local monitoring.
- **Direct Drag & Resize**: Drag any unlocked source box inside the stage or pull its corner handle to update OBS transforms live (`SetSceneItemTransform`). Quick actions include **Fit 16:9** and **Center**.

### 3. Source Layer Stack, Settings & Creation

- **Add Source (`+ Add Source`)**: Create a brand-new OBS input (`CreateInput` using your OS's synced `inputKinds` such as Browser, Image, Media, Text, Color) or link an existing OBS source into another scene (`CreateSceneItem`).
- **Layer Ordering & Locks**: Bring layers forward (`↑`) or send backward (`↓`) via `SetSceneItemIndex`, toggle visibility (`SetSceneItemEnabled`), lock movement (`SetSceneItemLocked`), or remove from scene (`RemoveSceneItem`).
- **Live Settings Inspector**: Load current input JSON settings (`GetInputSettings`), adjust fields like Browser URL or media path, and push updates directly via `SetInputSettings`.

---

## Filament Admin Control Deck (`/admin/obs-control-deck`)

The embedded admin view remains available for compact oversight inside the panel alongside **Workspace Relay** process controls (`Start / Restart local relay`), recent command audit logs, and direct Studio shortcuts.

### Outputs & Broadcast Controls

| Button | OBS Action | What it does |
| --- | --- | --- |
| Start / Stop Stream | `ToggleStream` | Starts or stops your live stream output in OBS. |
| Start / Stop Record | `ToggleRecord` | Begins or finalizes local video recording. |
| Pause Rec | `ToggleRecordPause` | Pauses/resumes recording inside the current file. |
| Virtual Cam | `ToggleVirtualCam` | Enables OBS Virtual Camera output for conferencing tools. |
| Save Replay | `SaveReplayBuffer` | Writes the instant-replay rolling buffer to disk. |
| Studio Mode | `SetStudioModeEnabled` | Toggles Preview/Program split mode. |
| Transition | `TriggerStudioModeTransition` | Cuts or fades your staged Preview scene into Program. |

---

## Live Scenes, Sources & Audio Mixer

- **Scenes**: When Studio Mode is **Off**, clicking a scene button runs `SetCurrentProgramScene` immediately. When Studio Mode is **On**, clicking a scene stages it into Preview (`SetCurrentPreviewScene`).
- **Create New OBS Scene**: Creates a scene directly inside OBS (`CreateScene`).
- **Scene Sources**: Shows items in the active Program scene so you can show or hide individual overlays/cameras (`SetSceneItemEnabled`).
- **Audio Inputs & Mixers**: Adjust channel mute toggles (`SetInputMute`) and volume sliders (`SetInputVolume` in dB).

---

## Mobile Wireless Deck (`/deck`)

Open **Mobile Deck** (`/deck`, redirecting to `/deck/{studio}`) from a phone or iPad browser for large, touch-friendly cue pads:

- **Outputs & Studio Mode** buttons tailored for thumb reach.
- **1-Click Broadcast Macros** listed ahead of scenes for fast production cues.
- **Scenes** highlighting the live Program scene over Reverb.
- **Quick Live Lower-Third / Alert Push** to update screen text or show a guest name mid-stream without returning to a desktop panel.
