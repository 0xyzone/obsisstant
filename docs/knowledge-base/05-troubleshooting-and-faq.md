---
title: Troubleshooting & Diagnostics
slug: troubleshooting
category: Operations
order: 5
summary: Quickly diagnose Reverb status badges, offline relays, OBS authentication mismatches, and cached Browser Sources.
---

## Top Bar Displays **Reverb offline**

The top-bar indicator probes the configured internal Reverb WebSocket port (`REVERB_SERVER_HOST` / `REVERB_SERVER_PORT`, defaulting to `127.0.0.1:8080`) on page load.

- Start or verify the Reverb server with:

```sh
./scripts/run_reverb.sh status
./scripts/run_reverb.sh start
```

- Check `.env` to confirm `REVERB_SERVER_PORT` matches your running daemon and web server proxy configuration.

---

## Studio Stays **Offline** in Control Deck

1. Inspect the relay process status in **Obs Studios** or terminal:

```sh
./scripts/run_obs_agent.sh status
./scripts/run_obs_agent.sh restart
```

2. Confirm OBS Studio is running and **Tools → WebSocket Server Settings → Enable WebSocket Server** is checked on port `4455`.
3. Verify the **OBS WebSocket Password** saved in `/admin/obs-studios` matches OBS Studio (leaving it blank only works if OBS WebSocket authentication is turned off).
4. Review the recent command audit table at the bottom of **Control Deck** for any failed OBS response messages.

---

## Scene Items Show No Options in Macro Builder

- **Scene Items** (`SetSceneItemEnabled`) sync dynamically for your current **Program** scene whenever `obs-agent` refreshes state.
- Switch OBS to the target scene and click **Sync OBS state** on the Control Deck—or enter the numeric `sceneItemId` manually using the Custom Scene Item fallback.

---

## Browser Source Looks Stale Inside OBS

If OBS caches an older version of a page or script, run **PressInputPropertiesButton** (`propertyName = refreshnocache`) from **Control Deck** macros or open the OBS Browser Source properties and click **Refresh cache of current page**.
