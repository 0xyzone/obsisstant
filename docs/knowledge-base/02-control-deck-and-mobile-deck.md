---
title: Control Deck & Mobile Wireless Deck
slug: control-deck
category: Core Features
order: 2
summary: Understand real-time telemetry, scene switching, Studio Mode, audio mixing, and wireless tablet/phone operation.
---

## OBS Live Control Deck (`/admin/obs-control-deck`)

The Control Deck mirrors your connected Studio's live state over Laravel Reverb (`obs.studio.{id}`) without polling loops.

### Studio Header & Telemetry

- **Active Studio selector**: Choose which registered OBS workstation to command.
- **Sync OBS state**: Sends `obsisstant.RefreshAllState` so scenes, audio channels, source items, and outputs re-hydrate immediately.
- **Live gauges**: Displays OBS **FPS**, **CPU usage**, **Memory**, **Skipped frames**, and **Studio mode** status.

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
