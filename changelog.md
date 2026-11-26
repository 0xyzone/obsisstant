# Changelog

All notable changes to **Obsisstant** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to Semantic Versioning (`major.minor.macro`):
- **Major (`X.0.0`)**: Breaking architectural changes, major rewrites, or incompatible schema/API shifts.
- **Minor (`X.Y.0`)**: New user-facing features, panels, resources, pages, or non-breaking capabilities.
- **Macro (`X.Y.Z`)**: Bug fixes, UI polish, refactors, configuration tweaks, and incremental patches.

## [v1.22.1] - 2026-11-27 | type: macro

> Fixed the Wave reaction speech bubble in `public/js/obs-mascot.js` so it says "Ask Oxy" instead of "Ask Lumi", and updated the default Gemini Interactions endpoint to `v1beta/interactions` for direct compatibility with Google AI Studio keys.

### Fixed
- Updated `REACTION_LINES.wave` in `public/js/obs-mascot.js` from `"Hey! Tap Ask Lumi or drag me anywhere."` to `"Hey! Tap Ask Oxy or drag me anywhere."`.
- Pointed `services.gemini.endpoint` to `https://generativelanguage.googleapis.com/v1beta/interactions` (`gemini-3.5-flash-lite`) so Google AI Studio `AQ.*` keys authenticate out of the box.

## [v1.22.0] - 2026-11-27 | type: minor

> Added hybrid Google Gemini AI support (`gemini-3.5-flash-lite` via the Interactions v1 API) for signed-in Studio operators in Oxy, with 12-hour response caching, zero-token local execution for live Studio/OBS commands, and automatic local fallback for guests or when no API key is configured.

### Added
- **Hybrid Google Gemini AI for Oxy (`services.gemini`)**: Signed-in operators now receive context-aware Gemini AI responses powered by `GEMINI_API_KEY` (`gemini-3.5-flash-lite` default), while guest visitors and live studio commands (`Start 5m timer`, `Show lower third`, `Switch view`, `Check studio status`) execute locally at 0 API tokens.

## [v1.21.1] - 2026-11-27 | type: macro

> Renamed the 3D mascot and assistant from Lumi to Oxy, and upgraded the chat engine with natural human-like conversation, small talk, multi-turn follow-up memory, richer Knowledge Base excerpts, and smarter live studio command parsing.

### Improved
- **Oxy Rebrand & Conversational Upgrade**: Renamed the mascot, drawer, and `/oxy/ask` endpoint to **Oxy**, added conversation history (`history`), natural greetings/small-talk/follow-ups, synonym-expanded Knowledge Base answers with readable excerpts, macro/view/scene listing and execution, and an animated typing indicator with dynamic follow-up suggestion chips.

## [v1.21.0] - 2026-11-27 | type: minor

> Turned Lumi into Obsisstant's built-in smart AI assistant across the app (including Filament panels, Studio Control Center, Mobile Deck, Knowledge Base, and Changelog), slowed Lumi's flight motion for buttery-smooth tracking, and refined speech bubbles to appear briefly on section arrivals and interactions with crisp, readable sizing on mobile and desktop.

### Added
- **Built-in Smart AI Assistant (`POST /lumi/ask`)**: Added `LumiAssistantService` and `LumiAssistantController` with a frosted-glass + metallic-gold Lumi chat drawer so visitors and operators can ask OBS & Obsisstant questions (powered by the Knowledge Base & OBS Request Catalog), generate lower-third/ticker copy ideas, and—when signed in—run live Studio/OBS actions (timers, lower thirds, scoreboards, screen switches, OBS stream/record/scene commands, and status checks) without external API keys.
- **App-Wide Mascot Presence**: Added `<x-obs-mascot mode="floating-assistant" />` across Filament panels (including Login), Studio Control Center, Mobile Deck, Knowledge Base, and Changelog while keeping Canvas Builder and Stage Player distraction-free.

### Improved
- **Buttery Flight & Auto-Hiding Speech Bubbles**: Slowed Lumi's follow lerp (`2.4`), breathing, and banking in `public/js/obs-mascot.js`, restricted speech bubbles to brief 3.2s pop-ins on section changes or interactions, and counter-scaled `.obs-mascot-bubble` and `.obs-mascot-ask-pill` so text stays crisp and readable on both mobile and desktop.

## [v1.20.2] - 2026-11-27 | type: macro

> Unified the homepage 3D mascot into a single non-clipping, GPU-composited floating buddy that flies out of the hero on scroll and can be dragged anywhere (holding for 5 seconds before returning).

### Fixed
- Pulled the Three.js camera back (`z = 5.75`) so the halo antenna, sparkles, and bounce animations never clip inside the canvas.
- Replaced the separate scroll-guide canvas with a single unified GPU-composited (`translate3d`) mascot that emerges directly from the hero section on scroll and supports smooth mouse/touch dragging with a 5-second hold before flying back to its scroll-guided position.

## [v1.20.1] - 2026-11-27 | type: macro

> Fixed Three.js module resolution (`esm.sh`) so the 3D mascot renders cleanly with zero console errors, removed all card/box containers around the mascot, and enabled free scroll-guided flight across the page.

### Fixed
- Resolved `Failed to resolve module specifier "three"` by importing Three.js and `RoomEnvironment` via `esm.sh` in `public/js/obs-mascot.js`.
- Removed background/border boxes from `<x-obs-mascot />` and `#mascot-scroll-guide` so the 3D black-and-gold mascot floats freely and flies across the viewport as you scroll.

## [v1.20.0] - 2026-11-27 | type: minor

> Redesigned the 3D mascot to a cute black-and-gold chibi silhouette with floating body, teardrop arms, halo antenna, and glossy eyes, fixed the homepage Live Toolkit tab visibility, and added Features Showcase and FAQs sections.

### Added
- Added a 6-card **Features Showcase** (`Made for real broadcasts.`) and an interactive **FAQs** accordion (`Common questions.`) to the homepage (`welcome.blade.php`), plus corresponding scroll-guide stops for Lumi.

### Changed
- Rebuilt `public/js/obs-mascot.js` into the cute black-and-gold chibi silhouette (obsidian shell, warm pearl oval face, glossy black eyes with catchlights, cute smile, floating mini body with gold chest button, floating teardrop arms, and gold halo antenna).

### Fixed
- Fixed `[hidden]` being overridden by inline `display: grid` on `#overlays` Live Toolkit tab panels in `welcome.blade.php` so tabs switch cleanly instead of stacking.

## [v1.19.0] - 2026-11-26 | type: minor

> Added Lumi, an interactive 3D metallic-gold and obsidian mascot across the homepage (with scroll-guided flying & pointing animations), Knowledge Base, Changelog, and Filament Login, and replaced the homepage preset list with a practical interactive Live Toolkit.

### Added
- Added `public/js/obs-mascot.js` and `<x-obs-mascot />` featuring a Three.js 3D metallic-gold & obsidian mascot with cursor/touch eye tracking, natural blinking, orbiting golden sparkles, and interactive reactions (Wave, Spin, Cheer, Dance, Peek), plus a responsive scroll-guided floating companion on the homepage.
- Replaced the homepage preset explorer with an interactive 4-tool Live Toolkit (`Starting Soon & Countdown`, `Lower Thirds & Alerts`, `Scoreboard & Ticker`, and `Phone Deck & Macros`) with live hands-on demo controls.

## [v1.18.0] - 2026-11-26 | type: minor

> Extended the shiny metallic gold and frosted glassmorphism theme across the homepage, Knowledge Base, and Filament Admin Panel, and redesigned the homepage with minimal, human-friendly copy and large thin Poppins headings.

### Changed
- Redesigned the homepage (`welcome.blade.php`) with large thin `Poppins` (`200`/`300`) display headings, shiny metallic gold gradient text and buttons, frosted glassmorphic cards, and concise human-friendly copy.
- Extended the frosted glassmorphism, ambient gold lighting, and shiny metallic gold accents to Knowledge Base (`knowledge-base.blade.php`, `knowledge-base-styles.blade.php`) and Filament Admin Panel (`filament-obs-theme.css`).

## [v1.17.4] - 2026-11-26 | type: macro

> Unified the application's visual theme with a metallic gold and frosted glassmorphism design system while keeping Poppins typography across every workspace.

### Changed
- Standardized Canvas Builder (`collection-builder.blade.php`) body typography to `Poppins` for consistent readability across the entire application.
- Upgraded Studio Control Center, Canvas Builder, Mobile Deck, and Filament Admin Panel surfaces, panels, topbars, and primary metallic gold buttons with coordinated frosted glassmorphism (`backdrop-filter: blur(...) saturate(145%)`), ambient gold radial lighting, and specular top-edge highlights.

## [v1.17.3] - 2026-11-26 | type: macro

> Added a global ultra-thin shiny metallic gold pill scrollbar (expanding on hover) and metallic gold text selection with black ink across all app pages and the Filament Admin Panel.

### Added
- Added `public/css/obs-chrome.css` (loaded globally via `<x-app-favicon />` and Filament `obsidian-styles`) styling a `5px` thin metallic gold pill scrollbar that expands to `9px` on hover, plus `::selection` with metallic gold background and `#070706` black text.

## [v1.17.2] - 2026-11-26 | type: macro

> Registered the Tailwind CSS v4 Vite plugin (`@tailwindcss/vite`) in `vite.config.js` to eliminate Lightning CSS `Unknown at rule` warnings during production builds.

### Fixed
- Added `tailwindcss()` from `@tailwindcss/vite` to `plugins` in `vite.config.js` so `@import 'tailwindcss'`, `@source`, and `@theme` directives in `resources/css/app.css` are compiled before CSS minification.

## [v1.17.1] - 2026-11-26 | type: macro

> Fixed layer rotation not being applied when loop/enter animations are active or when editing the currently live Screen View without switching views.

### Fixed
- Switched layer rotation in Canvas Builder and Stage Player to the independent CSS `rotate` property so `loop-pulse`, `loop-float`, and entrance `transform` animations no longer overwrite layer rotation.
- Fixed Stage Player to re-render the active view slot immediately (without replaying entrance animations) whenever the editor saves changes to the currently active Screen View or theme.

## [v1.17.0] - 2026-11-26 | type: minor

> Added a full "When Timer Ends (00:00)" action selector directly inside every Timer block (Canvas Builder Timer layer, View & Logic tab, Studio Control Center Timer & Clock block, and Mobile Deck Timer modal) supporting Stage Screen, Timer, and OBS Studio automation actions with clock-skew-immune execution.

### Added
- Added 12 selectable timer-end automation actions grouped by category: Switch Screen View, Show Pop-Up Alert Banner (custom title & message), Show Lower Third Banner (custom badge/title/subtitle), Hide Timer Layer(s) (with automatic unhide on Start/Reset), Reset Timer, Loop / Restart Countdown, Switch OBS Program Scene, Start/Stop OBS Stream, Start/Stop OBS Recording, and Run OBS Macro.
- Added the "When Timer Ends (00:00)" action selector and conditional target inputs directly inside the Canvas Builder Timer layer inspector (synced with View & Logic), the Studio Control Center Timer & Clock block, and the Mobile Deck Stage Timer Controls modal.

### Fixed
- Fixed Stage Player timer-end detection to use server-relative remaining milliseconds (`remainingMs <= 0`) instead of wall-clock `Date.now()`, preventing early or late automation triggers on skewed client clocks.
- Added idempotent timer-end trigger fallback from Studio Control Center and Mobile Deck so configured actions run reliably at `00:00` even when Stage Player is not currently open.

## [v1.16.0] - 2026-11-26 | type: minor

> Added broadcast font family controls, Canvas Builder Undo/Redo history and Delete-key layer removal, outline direction options (Inside / Outside / Center), inline Live View content editors in Canvas Builder and Studio Control Center, and direct pencil edit links on Studio Screen View tiles.

### Added
- Added 15 curated broadcast-ready font family options (with Bunny Fonts loaded in both Canvas Builder and Stage Player) for Collection Theme defaults and all text-bearing layers (`text`, `timer`, `scoreboard`, `lower_third`, `ticker`).
- Added per-view Undo / Redo history (`Cmd/Ctrl+Z`, `Cmd/Ctrl+Shift+Z`, `Ctrl+Y`, and Canvas toolbar buttons) plus `Delete` / `Backspace` keyboard removal of the selected layer with confirmation dialog in Canvas Builder.
- Added Outline Direction (`Inside Shape`, `Outside Shape`, `Center (Half In / Out)`) across outline-capable layers and `camera_cutout`, rendered identically in Canvas Builder and Stage Player via `applyStrokeStyle`.
- Added inline Live Content editors inside Canvas Builder (`Scoreboard`, `Lower Third`, `Ticker`, and `Timer`) with live canvas preview, instant `Push Live` actions, and automatic flush on `Save View`, plus Lower Third Badge and Ticker Badge/Headlines editors in Studio Control Center.
- Added a direct pencil edit button on every Screen View tile in Studio Control Center linking to Canvas Builder with `?view=<id>` pre-selected.

## [v1.15.1] - 2026-11-26 | type: macro

> Added opacity/alpha controls (interactive slider, exact % input, live checkerboard swatch, and RGBA/Hex8 parsing) to every color picker in Canvas Builder and Filament Forms.

### Added
- Added paired Opacity (0-100%) slider, numeric percentage input, checkerboard alpha preview swatch, and bidirectional #hex/#hex8/rgba() parsing to all Canvas Builder layer, Collection Theme, Default Background, and View Override color controls.
- Enabled RGBA alpha selection on Filament ColorPicker inputs in Screen Collection and Overlay Screen forms.

## [v1.15.0] - 2026-11-26 | type: minor

> Fixed excessive OBS WebSocket traffic and frequent disconnections, added confirmation dialogs to every delete/remove action, added automatic server file deletion on delete/replace plus a bulk unused & orphaned media cleaner, restricted Reverb status exclusively to Filament Admin Panel, and reorganized Filament navigation into clean grouped categories.

### Added
- Added ServerMediaCleanerService, Filament Storage & Cleaner bulk unused/orphaned file manager, and Canvas Builder Clean Unused Assets button with Unused tags and confirmation prompts.
- Added native confirmation dialogs to all remaining delete, remove, and reset controls across Canvas Builder (views, assets, layers, bulk cleanup), Studio Control Center (remove source from scene), and Mobile Deck (tile remove, modal delete, reset defaults).

### Changed
- Removed Reverb status pills and unused CSS from all public, Studio, Mobile Deck, Changelog, and Knowledge Base pages so Reverb status is shown exclusively inside Filament Admin Panel.
- Reorganized Filament Admin Panel navigation into ordered categories (Live Control, Stage & Overlays, Studio & Automation, Documentation) with clean labels and zero sort collisions.

### Fixed
- Fixed excessive OBS WebSocket chatter and frequent disconnections by replacing EventSubscription.All + High-Volume SceneItemTransformChanged with minimal low-volume subscriptions, batching full OBS hydration into 3 callBatch frames instead of 30-60 individual RPC calls, skipping non-audio inputs for mute/volume queries, adding 25s RFC 6455 WebSocket keepalive pings, debouncing volume/refresh bursts, raising stats/heartbeat intervals with a 95s lease TTL, and preventing dual-agent contention across both relay and single-studio modes.
- Fixed server storage leaks by deleting physical files automatically via ScreenAsset and ScreenCollection deleting hooks, auto-pruning unreferenced replaced background/stinger/layer files on update or view delete, and cleaning direct offline upload staging files immediately.

## [v1.14.0] - 2026-11-26 | type: minor

> Added the transformable Color Box canvas element (rectangle, square, circle) and full per-element styling controls for text, timers, scoreboards, lower thirds, tickers, glass cards, assets, cutouts, and embeds.

### Added
- Added the Color Box element (color_box) in Canvas Builder and Stage Player with Rectangle, Square, and Circle presets, solid/gradient/outline-only fills, rounded-edge controls, outline color/thickness/style, drop shadow, and aspect-locked resize for squares and circles
- Added comprehensive per-element inspector controls including font color, font weight, font family, alignment, padding, letter spacing, text shadow, digit & label font sizes/colors, background colors, card background & hours toggles, backdrop blur, fit modes, outline color/thickness/style, and layer stack bring-forward/send-backward ordering

## [v1.13.6] - 2026-11-26 | type: macro

> Fixed real-time Canvas Editor theme reflection and added Transparent and Animated Video/Image options for the Collection Default Background with 1:1 Stage Player sync.

### Added
- Added Collection Default Background controls in Canvas Builder (Theme Gradient, Solid Color, Transparent OBS Alpha, and Animated Video/Image Asset) with direct MP4/WebM/image upload pickers in both Theme and View tabs

### Fixed
- Fixed blank view theme_override fields persisting nulls that clobbered Collection Theme colors back to gold, wired real-time input/change listeners across all Theme and View controls, rendered live looping video backdrops and theme text/surface/accent colors inside Canvas Editor, and synced live Reverb theme updates directly to Stage Player

## [v1.13.5] - 2026-11-26 | type: macro

> Fixed continuous OBS reconnect loops and cross-PC collisions when running 2 or more OBS clients/studios simultaneously.

### Changed
- Defaulted the Studio Control Center Install Agent dialog to Bind to this Studio only when an account has 2 or more studios so each streaming PC binds cleanly to its own OBS Studio

### Fixed
- Guarded ConnectionClosed against failed initial dial attempts, added a connectObs concurrency lock, prevented multiple local relay workers from colliding on the same ws://127.0.0.1:4455 OBS endpoint, added exponential/auth-failure reconnect backoff, and added Pusher keepalive timeouts in obs-agent
- Added X-Obs-Agent-Instance header identification, bootstrap active_on_other_agent lease hints, and duplicate-relay takeover prevention in ObsAgentController so multiple PCs never fight over the same studio

## [v1.13.4] - 2026-11-26 | type: macro

> Added configurable transition duration controls (capped at 3000 ms maximum) to the Mobile Control Deck toolbar and Tile Editor with matching server-side validation.

### Added
- Added a live millisecond transition duration input beside the Mobile Deck global transition selector and inside the App Screen View Tile Editor modal, shown whenever a transition type is selected and capped at 3000 ms (0 ms for Cut)

### Changed
- Enforced the 3000 ms maximum transition duration across ScreenCollectionController validation, ScreenCollectionService view switching, Studio Control Center override input, and Stage Player runtime clamping

## [v1.13.3] - 2026-11-26 | type: macro

> Fixed persistent Install OBS Agent prompts and multi-device online/offline flapping when Obsisstant is open across multiple computers or mobile devices.

### Fixed
- Protected active online studios with a 75s per-agent lease in ObsAgentController and deterministic agent_instance_id tracking in obs-agent so idle secondary PCs can no longer downgrade a live studio to Offline or overwrite succeeded commands with failed acks
- Auto-hid the OBS Agent offline banner in Unified Screen Mode across Studio Control Center and Mobile Deck, switched modal and banner dismissal persistence from sessionStorage to localStorage, added Dismiss buttons across Studio, Mobile Deck, and Admin Dashboard, and stopped auto-opening the Install Agent modal once any studio in the workspace has connected

## [v1.13.2] - 2026-11-26 | type: macro

> Fixed countdown timer offset bug caused by server/client clock skew by anchoring timer math to server-computed milliseconds and monotonic browser time.

### Fixed
- Eliminated timer clock-skew jumps (e.g. a 5:00 timer starting at 6:17) by computing exact server-relative remaining_ms and elapsed_ms in ScreenCollectionService and ticking via monotonic performance.now() across Mobile Deck, Studio Control Center, and Stage Player
- Used millisecond UTC timestamps, made Start idempotent while running, preserved ceil precision on Pause/Add Seconds, and synced live timer mode and on-screen label updates directly to Stage Player

## [v1.13.1] - 2026-11-25 | type: macro

> Upgraded Mobile Control Deck with an interactive multi-control Timer Tile and a full Stage Timer Controls modal with presets, custom Min:Sec input, nudges, modes, and on-screen label.

### Added
- Added a live ticking Timer pill in the Mobile Deck toolbar and a full Stage Timer Controls modal supporting -1m/-30s/+30s/+1m nudges, 1m-15m presets with optional auto-start, custom Min:Sec duration, Countdown/Count-Up/Live Clock mode switching, and live on-screen timer label editing

### Changed
- Upgraded the Mobile Deck Timer Tile into a live interactive widget with progress bar and inline Start/Pause, Reset, -1m, +1m, and Set controls

## [v1.13.0] - 2026-11-25 | type: minor

> Replaced the fixed Mobile Deck button wall with a persistent, per-collection Configurable Tile Deck focused on switching App Screen Views inside the single OBS Browser Source.

### Added
- Added persistent deck_layout JSON storage on obs_screen_collections with automatic view-tile generation and PUT /studio/{studio}/collections/{collection}/deck-layout API with live Reverb sync
- Built in-place Edit Tiles Mode on Mobile Deck with Add/Edit/Delete Tile modal, Sync App Views, drag-and-drop & arrow reordering, 1x1 / 2x1 wide tile spans, 6 color accents, and configurable grid columns
- Supported 8 configurable tile action types: App Screen View switchers (with 16:9 mini-wireframes and ON AIR pills), Timer controls (with live ticking clock), Scoreboard deltas/swap/reset, Lower Third toggles/speaker presets, Pop-Up Alerts, Ticker toggles, Layer toggles, and optional OBS Actions/Macros

### Changed
- Defaulted Mobile Control Deck to Stage Tiles mode whenever Screen Collections exist while keeping Classic OBS controls tucked neatly under a secondary Classic OBS toggle

## [v1.12.2] - 2026-11-24 | type: macro

> Redesigned Studio Control Center into a balanced 2-column broadcast console with a full-width transport bar, tabbed Source Editor, and multi-column lower Audio Mixer deck.

### Changed
- Eliminated the empty center gap in Studio Control Center by replacing the 3-column layout with a full-width Broadcast Outputs & Macros bar over a 2-column Broadcast Workstation
- Moved Audio Mixer into a wide multi-column searchable fader grid paired with a tabbed Live Overlay Push / Command Log dock directly below the primary workspace
- Auto-selected the first source per scene in OBS Scenes Mode and organized Source Editor into compact OBS Properties and Position & Size tabs so the full console fits cleanly above the fold
- Expanded Unified Screen View pads edge-to-edge with auto-fit sizing and added a compact OBS Stage Binding summary card in the left rail

## [v1.12.1] - 2026-11-24 | type: macro

> Redesigned Studio Control Center, Canvas Builder, and Mobile Deck for clearer workflow hierarchy and fixed Stage Player viewport centering.

### Changed
- Redesigned Unified Screen Control Center with a guided 3-step left workflow, 16:9 mini-wireframe Screen View pads with ON AIR pills, and a spacious 4-tab live console (Timer & Clock, Scoreboard, Lower Third & Alerts, View Layers)
- Balanced Studio 3-column layout with height-capped scrollable Scenes, searchable Audio Mixer faders, calmer button hierarchy, and automatic hiding of legacy standalone Live Overlay Push in Unified Screen Mode
- Refined Visual Screen Canvas Builder with 3-tab left panel (Views, Assets, Theme), 1-click Element Palette, live layer previews, hover/selection labels, and keyboard save/nudge shortcuts
- Polished Mobile Control Deck Unified Screen Mode with integrated Lower Third & Alert controls and fixed Stage Player responsive viewport centering across non-1080p aspect dimensions

## [v1.12.0] - 2026-11-24 | type: minor

> Unified Single-Browser Screen Collections, Visual Canvas Composer, Transitions, and Live Remote Controls

### Added
- Screen Collections, Screen Views, and Server-Hosted Asset Library for running entire events and tournaments from one OBS Browser Source
- Interactive 16:9 Canvas Composer with layer drag/resize, animations, per-collection/per-view themes, transparent Camera Cutout SVG masking, and timer-end automation logic
- Double-buffered Stage Player (/stage/{token}) supporting Cut, Fade, Directional Slide, Wipe, Zoom, Branded Color Sweep, and Stinger WebM/MP4 transitions
- Unified Screen Mode toggle across Studio Control Center and Mobile Deck with live View Pads, Countdown/Stopwatch Timer, Tournament Scoreboard, Lower Thirds, Alerts, and 1-click Push Single Scene to OBS
- Filament ScreenCollections Resource and obs-agent Browser Source audio rerouting option

## [v1.11.6] - 2026-11-24 | type: macro

> Remove heavy Studio live preview and stage canvas, reorganizing sources, mixer, and overlays into a 3-column workstation

### Changed
- Remove continuous OBS screenshot preview streaming and interactive drag stage canvas from Studio Control Center
- Reorganize Studio layout into Show Control (left), Scene Sources stack + Source Editor split (center), and Audio Mixer, Overlays & Logs (right)
- Add explicit Edit, Remove, and Delete actions directly on every scene source row alongside full transform and property controls

## [v1.11.5] - 2026-11-24 | type: macro

> Eliminate multi-second scene switching lag with direct WebSocket whisper commands, non-blocking acks, and slim delta state sync

### Fixed
- Add client-obs.command whisper fast-path across Mobile Deck, Studio Control Center, and obs-agent backed by shared command_uuid audit logging
- Acknowledge fast OBS scene/control commands immediately before background state sync and omit full sceneItemsByScene bloat on scene switches
- Deduplicate redundant CurrentProgramSceneChanged pushes and retry 404 acks during fast whisper/HTTP races

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
