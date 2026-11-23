# Obsisstant Studio Relay Agent (`obs-agent`)

Lightweight cross-platform (macOS / Windows / Linux) bridge that connects your local **OBS Studio** (`obs-websocket` v5) **outbound** to **Obsisstant** over HTTPS + Laravel Reverb (`wss://`).

No router port-forwarding or public exposure of port `4455` is required — works identically with local MAMP (`https://ob`) and production cPanel / VPS deployments.

## Setup

1. Install dependencies inside `obs-agent/`:
   ```sh
   cd obs-agent
   npm install
   ```
2. Copy `.env.example` to `.env` and paste the Studio **Agent Token** from `/admin/obs-studios`:
   ```sh
   cp .env.example .env
   ```
3. In OBS Studio, open **Tools → WebSocket Server Settings**, enable **Enable WebSocket server** (default port `4455`), and set the matching password on your Studio profile in Obsisstant.
4. Start the agent:
   - **Local HTTPS (`https://ob`)**:
     ```sh
     NODE_EXTRA_CA_CERTS=/Applications/MAMP/conf/apache/ssl/ob-ca.crt npm start
     ```
   - **Production HTTPS (Let's Encrypt / AutoSSL)**:
     ```sh
     npm start
     ```
