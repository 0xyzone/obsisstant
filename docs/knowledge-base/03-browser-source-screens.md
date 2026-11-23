---
title: Browser Source Overlay Studio
slug: browser-overlays
category: Overlays & Screens
order: 3
summary: Design responsive HTML/CSS glass overlays, copy token-scoped OBS Browser Source URLs, push screens to scenes, and trigger live text updates.
---

## What ObsScreens Do

Each **OBS Screen** generates a unique, token-scoped overlay page at `/screens/{public_token}` designed specifically for **OBS Browser Sources** (`1920×1080` by default). Whenever you save content in the panel, update live fields from the Control Deck, or fire a `screen_update` macro step, Reverb immediately broadcasts `ObsScreenUpdated` to every open Browser Source instance.

---

## Available Templates

| Template Key | Layer Behavior | Ideal Use Case |
| --- | --- | --- |
| `starting_soon` | Full canvas slate | Pre-show holding slate with countdown timer and rolling ticker. |
| `main_cam` | Transparent overlay | Camera frame accent bars paired with lower-thirds. |
| `pip` | Transparent overlay | Picture-in-Picture framing alongside screen capture. |
| `brb` | Full canvas slate | Intermission slate with return timer and status banner. |
| `lower_thirds` | Transparent overlay | Speaker name, title, badge pill, alert pop-up, and ticker bar. |
| `ending` | Full canvas slate | End-of-stream outro credits and wrap-up message. |
| `custom` | Transparent overlay | Free-form glass overlay driven by custom state + CSS. |

---

## Adding an Overlay to OBS Studio

### Method 1 — Automatic **Push to OBS** (Fastest)

1. Configure **Target OBS Scene Name** and **OBS Browser Source Name** when creating or editing your screen in **OBS Studio → Obs Screens**.
2. Click **Push to OBS** from the table row action (or use the screen's scene mapping).
3. `obs-agent` creates the target scene if missing, inserts or updates the Browser Source URL at your exact canvas width/height, and marks it visible.

### Method 2 — Manual Browser Source Paste

Copy the read-only **OBS Browser Source URL** from the screen edit form (`/screens/{token}`), add a new **Browser** source in OBS Studio, paste the URL, and match `1920×1080`.
