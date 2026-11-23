<x-filament-panels::page>
    @php
        $studio = $this->getStudio();
        $studios = $this->getStudios();
        $screens = $this->getScreens();
        $macros = $this->getMacros();
        $logs = $this->getRecentLogs();
        $outputs = $studio?->getOutputs() ?? [];
        $stats = $studio?->getStats() ?? [];
        $scenes = $studio?->getScenes() ?? [];
        $sceneItems = $studio?->getSceneItems() ?? [];
        $audioInputs = $studio?->getAudioInputs() ?? [];
        $selectedScreen = $this->selectedScreenId ? $screens->firstWhere('id', $this->selectedScreenId) : null;
        $isLive = $studio ? $studio->isOnline() : false;
    @endphp

    <div wire:poll.15s class="obs-deck-stack">
        {{-- Studio Selector & Live Telemetry --}}
        <x-filament::section>
            <div class="obs-deck-toolbar">
                <div class="obs-deck-toolbar-group">
                    <label for="studio-selector" class="obs-deck-label">Active studio</label>
                    <select
                        id="studio-selector"
                        wire:model.live="studioId"
                        class="obs-deck-input obs-deck-select"
                    >
                        @forelse ($studios as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ ucfirst($item->status) }})</option>
                        @empty
                            <option value="">No studios configured</option>
                        @endforelse
                    </select>

                    @if ($studio)
                        <span class="obs-status-inline {{ $isLive ? 'is-live' : '' }}">
                            <span class="obs-status-dot" aria-hidden="true"></span>
                            <span>{{ ucfirst($studio->status) }}</span>
                        </span>
                        <span class="obs-deck-meta">
                            OBS {{ $studio->live_state['obsVersion'] ?? '—' }} · Program: <strong>{{ $studio->getCurrentProgramScene() ?? '—' }}</strong>
                        </span>
                    @endif
                </div>

                @if ($studio)
                    <div class="obs-btn-row">
                        <x-filament::button size="sm" color="gray" wire:click="sendCommand('obsisstant.RefreshAllState')">
                            Sync OBS state
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            size="sm"
                            color="warning"
                            :href="route('obs.deck.show', ['studio' => $studio->slug])"
                            target="_blank"
                        >
                            Open mobile deck
                        </x-filament::button>
                    </div>
                @endif
            </div>

            @if ($studio)
                <div class="obs-telemetry-grid">
                    <div class="obs-stat-tile">
                        <div class="obs-stat-label">FPS</div>
                        <div class="obs-stat-number">{{ $stats['activeFps'] ?? '—' }}</div>
                    </div>
                    <div class="obs-stat-tile">
                        <div class="obs-stat-label">CPU usage</div>
                        <div class="obs-stat-number">{{ isset($stats['cpuUsage']) ? $stats['cpuUsage'].'%' : '—' }}</div>
                    </div>
                    <div class="obs-stat-tile">
                        <div class="obs-stat-label">Memory</div>
                        <div class="obs-stat-number">{{ isset($stats['memoryUsage']) ? round($stats['memoryUsage']).' MB' : '—' }}</div>
                    </div>
                    <div class="obs-stat-tile">
                        <div class="obs-stat-label">Skipped frames</div>
                        <div class="obs-stat-number">{{ ($stats['renderSkippedFrames'] ?? 0).' / '.($stats['outputSkippedFrames'] ?? 0) }}</div>
                    </div>
                    <div class="obs-stat-tile">
                        <div class="obs-stat-label">Studio mode</div>
                        <div class="obs-stat-number">{{ $studio->isStudioModeEnabled() ? 'Active' : 'Off' }}</div>
                    </div>
                </div>
            @endif
        </x-filament::section>

        @if ($studio)
            {{-- Outputs & Broadcast Macros --}}
            <div class="obs-deck-two-col">
                <x-filament::section heading="Outputs & transitions">
                    <div class="obs-btn-row">
                        <x-filament::button
                            :color="! empty($outputs['streaming']) ? 'danger' : 'primary'"
                            wire:click="sendCommand('{{ ! empty($outputs['streaming']) ? 'StopStream' : 'StartStream' }}')"
                        >
                            {{ ! empty($outputs['streaming']) ? 'Stop stream' : 'Start stream' }}
                        </x-filament::button>

                        <x-filament::button
                            :color="! empty($outputs['recording']) ? 'warning' : 'gray'"
                            wire:click="sendCommand('{{ ! empty($outputs['recording']) ? 'StopRecord' : 'StartRecord' }}')"
                        >
                            {{ ! empty($outputs['recording']) ? 'Stop record' : 'Start record' }}
                        </x-filament::button>

                        <x-filament::button color="gray" wire:click="sendCommand('ToggleRecordPause')">
                            {{ ! empty($outputs['recordingPaused']) ? 'Resume rec' : 'Pause rec' }}
                        </x-filament::button>

                        <x-filament::button
                            :color="! empty($outputs['virtualCam']) ? 'success' : 'gray'"
                            wire:click="sendCommand('ToggleVirtualCam')"
                        >
                            Virtual cam
                        </x-filament::button>

                        <x-filament::button color="gray" wire:click="sendCommand('SaveReplayBuffer')">
                            Save replay
                        </x-filament::button>

                        <x-filament::button
                            :color="$studio->isStudioModeEnabled() ? 'warning' : 'gray'"
                            wire:click="toggleStudioMode"
                        >
                            Studio mode
                        </x-filament::button>

                        @if ($studio->isStudioModeEnabled())
                            <x-filament::button color="primary" wire:click="sendCommand('TriggerStudioModeTransition')">
                                Transition to live
                            </x-filament::button>
                        @endif
                    </div>
                </x-filament::section>

                <x-filament::section heading="Broadcast macros">
                    <div class="obs-btn-row">
                        @forelse ($macros as $macro)
                            <x-filament::button color="gray" wire:click="runMacro({{ $macro->id }})">
                                {{ $macro->name }}
                            </x-filament::button>
                        @empty
                            <p class="obs-deck-empty">No macros created yet.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            {{-- Scenes & Sources --}}
            <div class="obs-deck-two-col">
                <x-filament::section heading="Scenes">
                    <div class="obs-inline-form">
                        <input
                            type="text"
                            wire:model="newSceneName"
                            placeholder="New scene name"
                            class="obs-deck-input obs-inline-grow"
                        >
                        <x-filament::button size="sm" wire:click="createNewScene">Create scene</x-filament::button>
                    </div>

                    @if ($studio->isStudioModeEnabled())
                        <p class="obs-deck-empty" style="margin-bottom: 0.75rem;">
                            Studio Mode is ON — clicking a scene cues Preview; click Transition in Outputs to send it Live.
                        </p>
                    @endif

                    <div class="obs-scene-grid">
                        @forelse ($scenes as $scene)
                            @php
                                $sName = $scene['sceneName'] ?? '';
                                $isProgram = $studio->getCurrentProgramScene() === $sName;
                                $isPreview = $studio->getCurrentPreviewScene() === $sName;
                            @endphp
                            <x-filament::button
                                :color="$isProgram ? 'primary' : ($isPreview ? 'warning' : 'gray')"
                                wire:click="switchScene({{ \Illuminate\Support\Js::from($sName) }})"
                            >
                                {{ $sName }}
                            </x-filament::button>
                        @empty
                            <p class="obs-deck-empty">Waiting for obs-agent connection to sync OBS scenes.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section heading="Active scene sources">
                    <div class="obs-list-stack">
                        @forelse ($sceneItems as $item)
                            @php
                                $itemId = (int) ($item['sceneItemId'] ?? 0);
                                $enabled = (bool) ($item['sceneItemEnabled'] ?? false);
                                $sourceName = (string) ($item['sourceName'] ?? '');
                                $kind = (string) ($item['inputKind'] ?? '');
                            @endphp
                            <div class="obs-deck-subcard obs-deck-subcard-row">
                                <div>
                                    <div class="obs-deck-item-title">{{ $sourceName }}</div>
                                    <div class="obs-deck-item-meta">{{ $kind ?: 'source' }}</div>
                                </div>
                                <div class="obs-btn-row">
                                    @if ($kind === 'browser_source')
                                        <x-filament::button size="xs" color="gray" wire:click="refreshBrowserSource({{ \Illuminate\Support\Js::from($sourceName) }})">
                                            Refresh
                                        </x-filament::button>
                                    @endif
                                    <x-filament::button
                                        size="xs"
                                        :color="$enabled ? 'primary' : 'gray'"
                                        wire:click="toggleSceneItem({{ $itemId }}, {{ $enabled ? 'false' : 'true' }})"
                                    >
                                        {{ $enabled ? 'Visible' : 'Hidden' }}
                                    </x-filament::button>
                                </div>
                            </div>
                        @empty
                            <p class="obs-deck-empty">No active scene items synced yet.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            {{-- Audio Mixer & Live Screen Overlays --}}
            <div class="obs-deck-two-col">
                <x-filament::section heading="Audio mixer">
                    <div class="obs-list-stack-md">
                        @forelse ($audioInputs as $input)
                            @php
                                $inName = (string) ($input['inputName'] ?? '');
                                $muted = (bool) ($input['inputMuted'] ?? false);
                                $db = round((float) ($input['inputVolumeDb'] ?? 0), 1);
                            @endphp
                            <div class="obs-deck-subcard obs-deck-subcard-box">
                                <div class="obs-deck-mixer-head">
                                    <span class="obs-deck-item-title">{{ $inName }}</span>
                                    <div class="obs-btn-row">
                                        <span class="obs-deck-db">{{ $db }} dB</span>
                                        <x-filament::button
                                            size="xs"
                                            :color="$muted ? 'danger' : 'gray'"
                                            wire:click="toggleInputMute({{ \Illuminate\Support\Js::from($inName) }}, {{ $muted ? 'false' : 'true' }})"
                                        >
                                            {{ $muted ? 'Muted' : 'Live' }}
                                        </x-filament::button>
                                    </div>
                                </div>
                                <input
                                    type="range"
                                    min="-60"
                                    max="0"
                                    step="1"
                                    value="{{ $db }}"
                                    class="obs-range-slider"
                                    x-on:change="$wire.setInputVolumeDb({{ \Illuminate\Support\Js::from($inName) }}, parseFloat($event.target.value))"
                                >
                            </div>
                        @empty
                            <p class="obs-deck-empty">No OBS audio inputs detected yet.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section heading="Live screen overlay controller">
                    <div class="obs-list-stack-md">
                        <div class="obs-inline-form">
                            <select
                                wire:model.live="selectedScreenId"
                                class="obs-deck-input obs-deck-select obs-inline-grow"
                            >
                                @foreach ($screens as $scr)
                                    <option value="{{ $scr->id }}">{{ $scr->name }} ({{ $scr->template_type }})</option>
                                @endforeach
                            </select>
                            @if ($selectedScreen)
                                <x-filament::button size="sm" color="gray" tag="a" :href="$selectedScreen->getOverlayUrl()" target="_blank">
                                    Open overlay
                                </x-filament::button>
                                <x-filament::button size="sm" color="warning" wire:click="pushSelectedScreenToObs">
                                    Push to OBS
                                </x-filament::button>
                            @endif
                        </div>

                        <div class="obs-overlay-fields">
                            <input type="text" wire:model="liveGuestName" placeholder="Lower-third name" class="obs-deck-input">
                            <input type="text" wire:model="liveGuestRole" placeholder="Role or subtitle" class="obs-deck-input">
                        </div>

                        <div class="obs-btn-row">
                            <x-filament::button size="sm" color="primary" wire:click="updateLiveOverlay(true)">
                                Show lower third
                            </x-filament::button>
                            <x-filament::button size="sm" color="gray" wire:click="updateLiveOverlay(false)">
                                Hide lower third
                            </x-filament::button>
                        </div>

                        <div class="obs-deck-divider">
                            <div class="obs-overlay-fields">
                                <input type="text" wire:model="liveAlertTitle" placeholder="Alert title" class="obs-deck-input">
                                <input type="text" wire:model="liveAlertMessage" placeholder="Alert message" class="obs-deck-input">
                            </div>
                            <div class="obs-btn-row obs-deck-alert-actions">
                                <x-filament::button size="sm" color="warning" wire:click="triggerLiveAlert(true)">
                                    Push alert toast
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="triggerLiveAlert(false)">
                                    Dismiss alert
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Recent Command Audit Log --}}
            <x-filament::section heading="Recent OBS command log">
                <div tabindex="0" role="region" aria-label="Recent OBS command log" class="obs-table-wrap">
                    <table class="obs-data-table">
                        <thead>
                            <tr>
                                <th scope="col">Command</th>
                                <th scope="col">Status</th>
                                <th scope="col">Error</th>
                                <th scope="col" class="obs-num-col">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <th scope="row">{{ $log->request_type }}</th>
                                    <td>
                                        <span class="obs-command-status {{ $log->status === 'failed' ? 'is-error' : ($log->status === 'succeeded' ? 'is-ok' : '') }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->error_message ?? '—' }}</td>
                                    <td class="obs-num-col">{{ $log->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="obs-empty-row">No commands dispatched yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
