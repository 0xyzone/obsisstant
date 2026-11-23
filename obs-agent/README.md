# Obsisstant Studio Relay Agent (`obs-agent`)

Lightweight cross-platform (macOS / Windows / Linux) bridge that connects your local **OBS Studio** (`obs-websocket` v5) **outbound** to **Obsisstant** over HTTPS + Laravel Reverb (`wss://`).

No router port-forwarding or public exposure of port `4455` is required. With **Workspace Relay Mode** (`OBSISSTANT_RELAY_TOKEN`), you pair once and any Studio added, edited, or updated in `/admin/obs-studios` connects automatically without ever editing `.env` again.

## Quick Setup

### Option A — Workspace Auto-Sync Relay (Recommended)
1. Install dependencies once inside `obs-agent/`:
   ```sh
   cd obs-agent
   npm install
   ```
2. On local Mac (`https://ob`), use the **Start Local Relay** button inside `/admin/obs-studios` (or run `./scripts/run_obs_agent.sh start`) — it automatically configures `obs-agent/.env` with your Workspace Relay Key and connects all active Studios.
3. On a remote Windows/Mac streaming PC connecting to your production URL, copy the one-line pairing command shown under **Pair External Relay** in `/admin/obs-studios`:
   ```sh
   OBSISSTANT_URL=https://your-domain.com OBSISSTANT_RELAY_TOKEN=obs_relay_... npm start
   ```

### Option B — Per-Studio Isolated Token
Set `OBSISSTANT_AGENT_TOKEN` in `obs-agent/.env` using the **Relay Agent Bearer Token** from `/admin/obs-studios/{id}/edit` to lock one agent instance to one specific Studio.
