# Changelog

All notable changes to **Obsisstant** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to Semantic Versioning (`major.minor.macro`):
- **Major (`X.0.0`)**: Breaking architectural changes, major rewrites, or incompatible schema/API shifts.
- **Minor (`X.Y.0`)**: New user-facing features, panels, resources, pages, or non-breaking capabilities.
- **Macro (`X.Y.Z`)**: Bug fixes, UI polish, refactors, configuration tweaks, and incremental patches.

## [v1.11.4] - 2026-11-24 | type: macro

> Fix obs-agent Pusher WebSocket transport, add pending command fallback polling, and improve Studio/Deck feedback

### Fixed
- Override Pusher.Runtime WebSocket factory in obs-agent to use ws so TLS proxy connections succeed reliably on production shared hosting
- Add /api/obs-agent/commands/pending fallback polling, safe broadcast guards, and duplicate command protection in obs-agent
- Keep obs-agent/.env OBSISSTANT_RELAY_TOKEN automatically synced with root .env OBS_RELAY_TOKEN in ObsLocalRelayService and run_obs_agent.sh
- Warn clearly when OBS Agent command acknowledgment times out and allow double-click or Go Live scene switching in Studio Control Center

## [v1.11.3] - 2026-11-24 | type: macro

> Fix cross-origin deck URLs, LAN Echo host detection, Livewire Control Deck quotes, and snapshot fallback sync

### Fixed
- Use same-origin relative endpoint URLs across Mobile Deck and Studio Control Center so phones and alternate hosts never fail requests
- Adapt runtime Reverb Echo host and TLS to window.location on LAN hosts and add visibility and interval snapshot fallback sync
- Use Js::from for Filament Control Deck scene/source/audio actions and show an inline Studio Mode preview hint

## [v1.11.2] - 2026-11-24 | type: macro

> Fix Mobile Deck scene switching, dynamic scene sync, error feedback, and offline agent banner

### Fixed
- Add optimistic active scene toggling, command acknowledgment verification, snapshot fallback sync, and visible error toasts in mobile-deck.blade.php
- Dynamically rebuild Mobile Deck #scenes-grid on .obs.studio.state updates, add topbar studio switcher, and warn with a Connect / Install Agent banner when the studio is offline

## [v1.11.1] - 2026-11-24 | type: macro

> Prioritize zero-warning Mac 1-click Terminal installer over Gatekeeper-blocked Finder downloads

### Fixed
- Lead macOS onboarding with Copy 1-Click Mac Setup to bypass macOS Sequoia/Tahoe quarantine blocks and update macOS 15+ Open Anyway instructions for manual .zip downloads

## [v1.11.0] - 2026-11-24 | type: minor

> Zero-code Mac & Windows OBS Agent one-click installers and automatic offline onboarding prompts

### Added
- ObsAgentInstallerService and AgentInstallerController generating pre-configured Mac (.zip with LaunchAgent) and Windows (.zip with hidden Startup launcher) bundles plus short-lived 1-click setup scripts (/setup-agent/{token}/mac.sh and win.ps1)
- Automatic OS-aware Install OBS Agent onboarding prompt across /studio Control Center (auto-dismissing live on connect), Filament Dashboard, and Studios Manager with per-studio installer downloads

## [v1.10.5] - 2026-11-24 | type: macro

> Proxy production Reverb WebSockets through public/.htaccess for ob.suminshrestha.com.np

### Fixed
- Add domain-scoped HTTPS redirect and WebSocket [P,L] proxy rewrite rules in public/.htaccess forwarding /app traffic on ob.suminshrestha.com.np to ws://127.0.0.1:8085 without affecting local MAMP

## [v1.10.4] - 2026-11-24 | type: macro

> Harden run_reverb.sh for cPanel Linux hosts and add doctor diagnostics

### Fixed
- Expand PHP CLI discovery (EasyApache, CloudLinux Alt-PHP, php-cli, cgi-fcgi safe check, optional REVERB_PHP_BIN)
- Add stdin redirection, disown, startup crash detection, watchdog logging, and a doctor subcommand in scripts/run_reverb.sh

## [v1.10.3] - 2026-11-23 | type: macro

> Include /usr/local/bin/php in run_reverb.sh CLI lookup for cPanel cron jobs

### Fixed
- Add explicit /usr/local/bin/php candidate in scripts/run_reverb.sh so restricted cPanel cron environments locate PHP CLI >= 8.3 cleanly

## [v1.10.2] - 2026-11-23 | type: macro

> Fix false-positive Reverb status badge, suppress unconfigured Filament Echo errors, and load .env in run_reverb.sh

### Fixed
- Require BROADCAST_CONNECTION=reverb, non-empty Reverb credentials, and an authenticated Reverb API response before showing Reverb active
- Disable Filament Echo script initialization when Reverb broadcasting is inactive and load REVERB_SERVER_HOST/PORT from .env in run_reverb.sh

## [v1.10.1] - 2026-11-23 | type: macro

> Fix Studio scene item transforms reverting after updating in OBS

### Fixed
- Subscribe obs-agent to EventSubscription.SceneItemTransformChanged so OBS layout changes sync reliably
- Flush authoritative OBS transform and scene item state before command ack and protect Control Center transforms from stale snapshot overwrites

## [v1.10.0] - 2026-11-23 | type: minor

> Image source file upload to user-configured native Desktop/Mac local storage with Finder folder browser and Storage Settings page

### Added
- Storage Settings Page (/admin/studio-settings & ObsStudio override): Configure native Desktop/Mac local storage folder per account or studio override, apply quick OS presets, or launch the native Finder/Explorer folder picker via obs-agent (obsisstant.PickLocalDirectory)
- Image Source Upload Flow (/studio & ObsMediaStorageService): Upload PNG/JPG/GIF/WebP images directly in both Add Source and Image Source Properties with instant thumbnail preview, staging/one-time signed agent download (/api/obs-agent/media/{token} + obsisstant.SaveMediaFile), offline local-disk fallback, and live OBS source update

## [v1.9.0] - 2026-11-23 | type: minor

> 100% WebSocket preview whispers over Reverb and strict per-user multi-tenant isolation with self-registration and personal Relay tokens

### Added
- Pure WebSocket Preview Whispers (obs-agent, Reverb, /studio): Authorized obs-agent on private-obs.studio.{id} to receive client-obs.preview.subscribe controls and stream compressed frames directly via client-obs.preview.frame whispers over Reverb RAM with zero per-frame PHP/MySQL HTTP requests (keeping HTTP preview endpoints only as fallback)
- Strict Per-User Multi-Tenant Ownership (add_user_scoping_and_relay_token): Scoped obs_studios, obs_screens, obs_macros, Studio Control Center, Mobile Deck, Filament resources/widgets, and private Reverb broadcast channels strictly to the owning user_id
- Self-Registration & Personal Account Relay Tokens (Auth\Register, /admin/register, AuthenticateObsAgent, ListObsStudios): Enabled username-based user registration with auto-provisioned usr_ personal Relay Tokens and in-app Pair OBS Agent setup/rotation modal

### Changed
- Agent Bootstrap Reconciliation Polling: Increased backup catalog reconciliation interval to 5 minutes since private per-user relay channels push catalog updates immediately

## [v1.8.1] - 2026-11-23 | type: macro

> Load-balanced preview delivery over Reverb WebSocket with frame deduplication, file caching, transient preview leases, and incremental obs-agent sync

### Changed
- Preview Pipeline Performance (ObsPreviewFrameUpdated, config/reverb.php, ObsAgentController, /studio): Raised Reverb message limits to 256 KB to push 640px JPEG frames directly over WebSocket without /preview-frame HTTP GET polling, routed transient frame cache off MySQL onto file cache, and replaced aggressive 550ms fetches with 2.5s balanced / 1.2s active modes plus automatic background-tab pause
- Ephemeral Preview Lease & Incremental obs-agent Sync (ObsCommandService::dispatchTransient, obs-agent): Kept SetPreviewStream toggles out of obs_command_logs, added SHA-1 frame hash deduplication to skip static scene uploads, parallelized scene/input hydration with Promise.all, patched SceneItemTransformChanged incrementally, and throttled unchanged telemetry pushes

### Fixed
- Client Request Contention (/studio): Coalesced snapshot requests with single-flight locking, guarded dragged stage boxes against mid-drag DOM re-renders, and eliminated redundant Echo/polling contention

## [v1.8.0] - 2026-11-23 | type: minor

> Visual per-source OBS properties editor, human-readable input kind labels, empty JSON object payload fixes, and reliable action acknowledgment with live snapshot fallback

### Added
- Visual Source Properties Editor (/studio): Auto-loads live OBS properties upon selection and renders type-aware UI controls including color picker with alpha opacity (ABGR integer conversion), browser URL/dimensions/FPS and cache refresh, image/media paths and playback toggles, and text properties, keeping raw JSON under an Advanced disclosure
- Contextual Source Lifecycle Controls: Human-friendly Add Source dialog with typed fields per OBS input kind, inline Rename Source (SetInputName), intuitive Width/Height (px) transform sizing, and clear Remove from Scene vs Delete Source Permanently actions
- Live Control Center Snapshot Sync (GET /studio/{studio}/snapshot): Command acknowledgment polling, success/error toast feedback, live Recent Command Log refresh, and snapshot preview fallback

### Fixed
- CreateInput & Empty Payload JSON Serialization: Preserved empty PHP arrays as JSON {} in ObsCommandService, ObsCommandDispatched broadcasts, obs-agent bootstrap, and obs-agent normalizeObsPayload so OBS WebSocket v5 never rejects inputSettings
- Input Kind Dropdown Labels & Transform Sizing: Replaced raw snake_case OBS input kinds with readable labels across UI dropdowns and computed aspect-safe scaleX/scaleY or bounds updates cleanly from pixel width and height

## [v1.7.0] - 2026-11-23 | type: minor

> Environment-aware white/gold favicons, ASCII preview frame cache fix with default OBS Live View, and Scene Collection switch/create

### Added
- Environment Favicons (favicon-local.svg, favicon-prod.svg, x-app-favicon): Renders a white symbol in local/non-production tabs and metallic gold in production across public pages and Filament panels
- OBS Scene Collection Management (obs-agent, /studio, ObsRequestCatalog): Syncs sceneCollections & currentSceneCollection from OBS WebSocket v5 and adds remote SetCurrentSceneCollection switching and CreateSceneCollection creation

### Fixed
- Live Preview Image Cache & Stage Accuracy: Stored preview frames as UTF-8-safe ASCII base64 in Cache (resolving MySQL SQLSTATE 1366 error), enabled OBS Pixel Feed by default when online, aligned bounding boxes via OBS sceneItemTransform width/height and alignment bits, fixed [hidden] CSS visibility on Studio Mode transition button, and unified Obsisstant branding

## [v1.6.0] - 2026-11-23 | type: minor

> Standalone /studio Control Center with full Scene/Source CRUD, interactive 16:9 Live View stage, and HTTPS Vite hot-reload

### Added
- Standalone Studio Control Center (/studio): Dedicated full-screen 3-column broadcast director workspace outside Filament with scene create/rename/delete, source layer stacking, drag-and-resize 16:9 canvas builder, live settings JSON inspector, audio mixer, and overlay push controls
- Multi-mode Live View & obs-agent v1.6 upgrades: Expanded obs-agent telemetry (sceneItemsByScene, allInputs, inputKinds, videoDimensions), on-demand OBS pixel snapshot feed (GetSourceScreenshot via /api/obs-agent/preview-frame + ObsPreviewFrameUpdated), and local OBS Virtual Cam WebRTC preview option
- Vite HTTPS Live Auto-Reload (vite.config.js, scripts/run_vite.sh, x-dev-hot-reload): Configured Vite dev server with MAMP ob.crt/ob.key TLS on port 5173 and safe local-only hot-reload client across Landing, Studio, Knowledge Base, Changelog, Mobile Deck, and Filament panels

## [v1.5.0] - 2026-11-23 | type: minor

> Guided OBS WebSocket v5 Macro Builder with synced studio pickers and modular Markdown Knowledge Base across public docs and Filament panel

### Added
- Guided OBS Macro Builder (ObsRequestCatalog & ObsMacroResource): Grouped searchable OBS WebSocket v5 request picker, dynamic synced Scene/Audio Input/Scene Item dropdowns with custom manual override, typed boolean/numeric payload inputs, and Custom OBS command fallback
- Modular Markdown Knowledge Base (docs/knowledge-base/*.md, KnowledgeBaseService, /knowledge-base, /admin/knowledge-base): Five Markdown operator guides plus a searchable OBS WebSocket v5 Command Reference table accessible on both public pages and Filament admin sidebar

### Fixed
- Full-width Filament resource form layout: Added columnSpanFull() across top-level Sections in ObsMacroResource, ObsScreenResource, and ObsStudioResource to eliminate empty half-width column gaps

## [v1.4.0] - 2026-11-23 | type: minor

> Unslop landing page with purposeful motion, cross-app brand version pills, and live top-bar Reverb server status

### Added
- ReverbStatusService & top-bar Reverb indicator: Server-side TCP socket health check and shared Reverb active/offline status pill rendered in the top bar across Landing, Changelog, Mobile Deck, and Filament panels (including login)
- Cross-app brand version pill: Compact semantic version pill placed directly beside the Obsisstant brand lockup across app headers and Filament sidebar/topbar logo

### Changed
- Unslopped Landing Page (/): Redesigned welcome view on stepped matte obsidian surfaces (#070706 to #1B1A17) with Poppins 400-600 hierarchy, coded interactive OBS Switcher & Overlay Explorer, CSS View Timeline scroll reveals with IntersectionObserver fallback, and @starting-style popover drawer transitions

## [v1.3.1] - 2026-11-23 | type: macro

> Detach local obs-agent watchdog session and resolve obs-agent/.env path independently of working directory

### Fixed
- **Background Watchdog (`scripts/run_obs_agent.sh`, `obs-agent/src/index.js`)**: Launched `obs-agent` inside a detached POSIX session (`setsid`, `</dev/null`) and loaded `obs-agent/.env` relative to `import.meta.url` so both the admin UI button and CLI watchdog keep running reliably.

## [v1.3.0] - 2026-11-23 | type: minor

> Multi-Studio Workspace Auto-Sync Relay, Zero-.env Studio Management, UI Relay Controls & Edit Token Fix

### Added
- **Workspace Auto-Sync Relay Mode**: Added single-key multi-studio pairing (`OBS_RELAY_TOKEN` in `config/obs.php`, dual-mode `AuthenticateObsAgent`, `ObsAgentController` relay bootstrap, and `ObsRelayCatalogUpdated` Reverb sync event) plus dynamic multi-studio `StudioWorker` orchestration in `obs-agent/src/index.js` so adding or updating Studios in `/admin/obs-studios` syncs automatically with zero `.env` edits.
- **Local Relay Watchdog & Admin UI Controls**: Added `scripts/run_obs_agent.sh`, `ObsLocalRelayService`, live Start/Restart/Stop Local Relay buttons, and a one-click Pair External Relay modal in `ListObsStudios` and `ObsControlDeck`.

### Fixed
- **Blank Agent Token on Studio Edit (`EditObsStudio`)**: Fixed hidden attribute stripping during Filament form hydration (`mutateFormDataBeforeFill`) so `Per-Studio Relay Token` and `OBS WebSocket Password` populate cleanly on edit.

## [v1.2.4] - 2026-11-23 | type: macro

> Remove phantom outer box around dashboard stats widget and align Studio roster table columns

### Fixed
- **Dashboard Stats Wrapper (`filament-obs-theme.css`)**: Excluded `.fi-section-not-contained` from `.fi-section` card styling and reset its container padding so the 4 KPI stat cards sit cleanly without an outer border wrapper.
- **KPI Gold Accent & Roster Table Alignment**: Scoped the gold stat accent strictly to the first stat column (`.fi-wi-stats-overview .fi-section-content > .fi-grid-col:first-child`) and added explicit `text-align: start` / `text-align: end` rules to `.obs-data-table` headers and row cells.

## [v1.2.3] - 2026-11-23 | type: macro

> Fix broken grid layout and sidebar footer spacing on OBS Control Deck by replacing uncompiled Tailwind classes with dedicated theme CSS

### Fixed
- **OBS Control Deck Layout & Sidebar Footer**: Replaced uncompiled Tailwind grid/spacing utilities (`sm:grid-cols-5`, `lg:grid-cols-2`, `space-y-6`, `px-4 py-3`) in `obs-control-deck.blade.php` and `sidebar-version.blade.php` with explicit responsive CSS grid classes inside `public/css/filament-obs-theme.css` and added `<select>` arrow clearance.

## [v1.2.2] - 2026-11-23 | type: macro

> Unslop Filament dashboard with restrained gold-and-obsidian surfaces, studio workspace table, and 4px spacing

### Added
- **Studio Workspace Dashboard Widget (`ObsStudioWorkspaceWidget`)**: Replaced the generic greeting card on `/admin` with an asymmetric 2/3 responsive studio roster table (`role="region"`, sticky headers, container-query mobile stacking) paired with a recent OBS command feed and shortcut panel.

### Changed
- **Unslopped Gold & Black Filament Chrome (`filament-obs-theme.css`)**: Replaced background blobs/grid, gradient text, heavy shadows, and pill bloat with stepped matte obsidian surfaces (`#070706`–`#1B1A17`), quiet `#CDAD69` hairline borders, sentence-case Poppins (`400–600`) hierarchy, nested `8 / 12 / 16px` radii, and calm `140ms` surface transitions.

## [v1.2.1] - 2026-11-23 | type: macro

> Switch typography to clean Poppins and restyle gold glassmorphism using custom 7-stop metallic gold palette

### Changed
- **Typography (`Poppins`)**: Replaced `Syne` and `Plus Jakarta Sans` with clean geometric `Poppins` (`300–800`, `tabular-nums` KPIs) across `/`, `/admin`, `/changelog`, and `/deck/{studio}`.
- **Metallic Gold & Charcoal/Bronze Glassmorphism**: Applied the 7-stop brushed metallic palette (`#292C31`, `#070706`, `#2D1F11`, `#89612B`, `#AC8B56`, `#CDAD69`, `#F4E185`) with smoked frosted glass gradients, specular bevel highlights, and metallic foil typography.

## [v1.2.0] - 2026-11-23 | type: minor

> Obsidian Gold Glassmorphism Landing Page, Filament v5 Panel Redesign, Sans Typography System & Mobile Dock

### Added
- **Interactive Product Showcase Landing Page (`/`)**: Added `LandingController` and a glassmorphic obsidian-and-gold showcase at `/` featuring oversized `Syne` sans typography, interactive live scene/lower-third Mobile Deck preview, capabilities bento grid, and 7 interactive screen preset canvases.
- **Filament Dashboard OBS Telemetry Widget**: Added `ObsStatsOverview` widget displaying real-time online studio count, overlay canvases, broadcast macros, and current release version.

### Changed
- **Filament Admin Panel Obsidian Glass Theme (`/admin`)**: Restyled Filament (`filament-obs-theme.css`) with forced obsidian dark mode, bright `#FFD700` gold accents, `Syne` + `Plus Jakarta Sans` + `JetBrains Mono` sans typography, frosted login card, floating mobile bottom navigation dock, and upgraded `ObsControlDeck` stat tiles.
- **Unified Typography & Gold Palette Across Views**: Aligned `/changelog` and `/deck/{studio}` mobile Stream Deck views to the `Syne` + `Plus Jakarta Sans` + bright gold design system.

## [v1.1.1] - 2026-11-23 | type: macro

> Remove broken Echo stub generated by broadcasting install to fix Vite dev server overlay error

### Fixed
- **Vite Dev Server (`resources/js/app.js`)**: Removed the unused `./echo` import and `resources/js/echo.js` stub (`laravel-echo` / `pusher-js`) left behind by `install:broadcasting --without-node`, resolving the `[plugin:vite:import-analysis]` overlay on `/` while keeping Filament, overlays (`/screens/{token}`), and the mobile deck (`/deck/{studio}`) running on `public/js/filament/filament/echo.js`.

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
