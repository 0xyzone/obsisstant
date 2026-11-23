---
title: Getting Started & Relay Pairing
slug: getting-started
category: Setup & Architecture
order: 1
summary: Connect OBS Studio on your workstation to Obsisstant through Laravel Reverb with zero router port-forwarding.
---

## How Obsisstant Works

Obsisstant splits control across three layers so your workstation never needs open inbound firewall ports:

1. **Web Console & Mobile Deck**: Switch scenes, edit overlays, or trigger macros from `/admin` or `/deck`.
2. **Laravel Reverb Broker**: Broadcasts authorized studio events and listens on your internal Reverb WebSocket port (`8080` by default). The badge in the top bar shows **Reverb active** when the server socket is reachable.
3. **Local Relay Daemon (`obs-agent`)**: Runs next to OBS Studio, dials out to your Obsisstant server over HTTPS/WSS, and sends local commands straight to OBS on port `4455`.

---

## Step 1: Enable OBS WebSocket v5

1. Open **OBS Studio** (v28 or newer).
2. Go to **Tools → WebSocket Server Settings**.
3. Check **Enable WebSocket Server**.
4. Keep **Server Port** at `4455`.
5. If authentication is enabled, copy the **Server Password** (or set a password you remember).

---

## Step 2: Configure Your Studio in Obsisstant

1. Open **OBS Studio → Obs Studios** (`/admin/obs-studios`).
2. Create or edit a studio profile (`Main Studio` by default):
   - **Local OBS Host**: `127.0.0.1` (used locally by `obs-agent` to talk to OBS).
   - **OBS WebSocket Port**: `4455`.
   - **OBS WebSocket Password**: Optional; stored encrypted and pushed automatically to running relays.
   - **Studio Enabled**: Toggle **on** so active relay daemons subscribe to it.

---

## Step 3: Pair & Run the Relay Agent

### Option A — Workspace Auto-Sync Mode (Recommended)

Set `OBS_RELAY_TOKEN` once in the app's root `.env`, then copy that matching key into `obs-agent/.env`:

```env
OBSISSTANT_URL=https://ob
OBSISSTANT_RELAY_TOKEN=your_workspace_relay_token
```

Every active Studio created in `/admin/obs-studios` syncs automatically—including password or port edits—without updating `.env` per studio.

### Option B — Standalone Per-Studio Token

If a separate machine connects to just one Studio, copy **Per-Studio Relay Token** from that Studio's edit page and set `OBSISSTANT_AGENT_TOKEN` in `obs-agent/.env` on that machine.

### Starting the Daemons

You can start or restart the local relay directly from **Control Deck** or **Obs Studios**, or manage both daemons from the terminal:

```sh
./scripts/run_reverb.sh start
./scripts/run_obs_agent.sh start
```
