---
title: Broadcast Macros & Automation
slug: broadcast-macros
category: Automation
order: 4
summary: Chain OBS WebSocket v5 commands, delays, and live Reverb overlay patches into single-tap broadcast sequences.
---

## How Macros Execute

A **Broadcast Macro** (`/admin/obs-macros`) executes an ordered sequence of steps whenever you click **Run Macro** from Control Deck, Mobile Deck, or the Macros table:

1. **Update Screen Overlay (Reverb)** steps fire immediately over WebSockets to connected OBS Browser Sources (`ObsScreenUpdated`).
2. **OBS WebSocket Command** and **Delay (ms)** steps compile into an ordered queue:
   - A **single OBS command** dispatches directly (`this.obs.call(requestType, requestData)`).
   - **Two or more sequential OBS / Delay steps** bundle cleanly into an atomic `obsisstant.ExecuteBatch` call via `obs-websocket-js` `callBatch()`, preserving exact wait timings (`Sleep` / `sleepMillis`).

---

## Step Types Explained

### 1. OBS WebSocket Command

Select any built-in action from the **OBS Action / Request Type** dropdown (grouped by Scenes, Outputs, Audio, and Sources).

- **Synced Dropdowns**: When a Studio has connected at least once, fields like **Target OBS Scene**, **Audio / Input Source**, and **Scene Source Item** populate directly from your live OBS setup.
- **Offline / Custom Override**: Choose *Enter custom...* (or type directly when offline) if creating cues before OBS connects.
- **Custom Command Fallback**: Pick **Custom OBS WebSocket v5 Request** to run third-party plugin requests (e.g., Move Transition vendor calls) with raw key/value payload pairs.

### 2. Update Screen Overlay (Reverb)

Target any saved **OBS Screen** by slug and patch its live state keys:

| State Key | Type | Effect |
| --- | --- | --- |
| `headline` / `subheadline` | Text | Updates main slate title & subtitle immediately. |
| `guest_name` / `guest_role` | Text | Updates speaker lower-third card text. |
| `show_lower_third` | `true` / `false` | Reveals or hides the speaker lower-third overlay. |
| `show_countdown` | `true` / `false` | Turns the pre-show or BRB timer on or off. |
| `countdown_seconds` | Number | Sets countdown duration (for example `180`). |
| `alert_visible` / `alert_title` / `alert_message` | Boolean / Text | Displays or dismisses the pop-up alert toast. |

### 3. Delay (ms)

Waits the configured number of milliseconds (e.g., `500` ms) between OBS actions—useful when allowing an OBS transition to finish before muting a microphone or hiding a source.
