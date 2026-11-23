# Changelog

All notable changes to **Obsisstant** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to Semantic Versioning (`major.minor.macro`):
- **Major (`X.0.0`)**: Breaking architectural changes, major rewrites, or incompatible schema/API shifts.
- **Minor (`X.Y.0`)**: New user-facing features, panels, resources, pages, or non-breaking capabilities.
- **Macro (`X.Y.Z`)**: Bug fixes, UI polish, refactors, configuration tweaks, and incremental patches.

## [v1.1.0] - 2026-11-23 | type: minor

> Multi-Studio OBS Control Deck, Laravel Reverb Real-Time Sync, Outbound Studio Relay Agent, Screen Designer & Browser Overlays

### Added
- **Laravel Reverb & Apache WebSocket Proxy**: Installed Laravel Reverb (`wss://ob/app` via Apache `mod_proxy_wstunnel`), Filament Echo broadcasting config, `scripts/run_reverb.sh` cPanel/local watchdog, and `cpanel-root.htaccess`.
- **Multi-Studio OBS Registry & Node.js Relay Agent (`obs-agent/`)**: Added `obs_studios` and `obs_command_logs` models/API (`/api/obs-agent/*`) plus cross-platform Node.js `obs-websocket-js` v5 relay agent connecting outbound behind NAT/firewalls.
- **Dynamic Screen Designer & Live Browser Source Overlays (`/screens/{token}`)**: Added 7 preset templates (`starting_soon`, `main_cam`, `pip`, `brb`, `lower_thirds`, `ending`, `custom`), live Reverb-driven countdowns, tickers, alerts, lower-thirds, and 1-click Push to OBS.
- **OBS Live Control Deck, Macros & Mobile Stream Deck (`/deck/{studio}`)**: Added Filament `ObsControlDeck` (scenes, Studio Mode, outputs, audio mixer, sources, macros, telemetry) and responsive gold-and-black mobile touch deck.

## [v1.0.1] - 2026-11-23 | type: macro

> Serve Vite dev server over HTTPS to resolve Mixed Content console errors

### Fixed
- **Vite Dev Server HTTPS**: Configured `VITE_DEV_SERVER_KEY` and `VITE_DEV_SERVER_CERT` in `.env` (and documented in `.env.example`) so `npm run dev` serves scripts, styles, and fonts over `https://ob:5173` with valid SSL and CORS.

## [v1.0.0] - 2026-11-23 | type: major

> Initial Stage — Local HTTPS VHost, Versioning & Changelog UI, Task Automation, Username Auth & Filament Admin

### Added
- **Local HTTPS & MAMP VHost**: Configured Apache SSL virtual host for `https://ob` with local Root CA (`Obsisstant Local Root CA`) and automatic HTTP-to-HTTPS redirect.
- **Semantic Versioning & Changelog System**: Added `changelog.md` starting at `v1.0.0`, `ChangelogService`, `version:bump` Artisan command (`major`, `minor`, `macro`), and a responsive gold-and-black glassmorphism UI at `/changelog`.
- **Task Completion Automation**: Configured `scripts/notify_ntfy.php` for `obsisstant-tasks` notifications, Git auto-commit/push workflow, and agent guidelines in `AGENTS.md` and `CLAUDE.md`.
- **Extended User Model & Schema**: Added required unique `username`, nullable unique `contact_number`, nullable `current_address`, and made `email` nullable on `users`.
- **Filament Admin Panel (`/admin`)**: Installed Filament v5 panel (`admin`) with username authentication, customized `make:filament-user` CLI (username, password, name only), and created `Super Admin` (`admin`).
