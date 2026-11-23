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
    @endphp

    <div wire:poll.15s class="space-y-6">
        {{-- Studio Selector & Live Telemetry --}}
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <label for="studio-selector" class="text-sm font-semibold">Active Studio:</label>
                    <select
                        id="studio-selector"
                        wire:model.live="studioId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        @forelse ($studios as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ strtoupper($item->status) }})</option>
                        @empty
                            <option value="">No studios configured</option>
                        @endforelse
                    </select>

                    @if ($studio)
                        <x-filament::badge :color="match($studio->status) { 'streaming' => 'danger', 'recording' => 'warning', 'online' => 'success', default => 'gray' }">
                            {{ strtoupper($studio->status) }}
                        </x-filament::badge>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            OBS {{ $studio->live_state['obsVersion'] ?? '—' }} · Program: <strong>{{ $studio->getCurrentProgramScene() ?? '—' }}</strong>
                        </span>
                    @endif
                </div>

                @if ($studio)
                    <div class="flex flex-wrap items-center gap-2">
                        <x-filament::button size="sm" color="gray" wire:click="sendCommand('obsisstant.RefreshAllState')">
                            Sync OBS State
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            size="sm"
                            color="warning"
                            :href="route('obs.deck.show', ['studio' => $studio->slug])"
                            target="_blank"
                        >
                            Open Mobile Deck
                        </x-filament::button>
                    </div>
                @endif
            </div>

            @if ($studio)
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs text-gray-500">FPS</div>
                        <div class="text-lg font-bold">{{ $stats['activeFps'] ?? '—' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs text-gray-500">CPU Usage</div>
                        <div class="text-lg font-bold">{{ isset($stats['cpuUsage']) ? $stats['cpuUsage'].'%' : '—' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs text-gray-500">Memory</div>
                        <div class="text-lg font-bold">{{ isset($stats['memoryUsage']) ? round($stats['memoryUsage']).' MB' : '—' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs text-gray-500">Skipped (Render / Out)</div>
                        <div class="text-lg font-bold">{{ ($stats['renderSkippedFrames'] ?? 0).' / '.($stats['outputSkippedFrames'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                        <div class="text-xs text-gray-500">Studio Mode</div>
                        <div class="text-lg font-bold">{{ $studio->isStudioModeEnabled() ? 'ENABLED' : 'OFF' }}</div>
                    </div>
                </div>
            @endif
        </x-filament::section>

        @if ($studio)
            {{-- Outputs & Broadcast Macros --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-filament::section heading="Outputs & Transitions">
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button
                            :color="! empty($outputs['streaming']) ? 'danger' : 'primary'"
                            wire:click="sendCommand('{{ ! empty($outputs['streaming']) ? 'StopStream' : 'StartStream' }}')"
                        >
                            {{ ! empty($outputs['streaming']) ? 'Stop Stream' : 'Start Stream' }}
                        </x-filament::button>

                        <x-filament::button
                            :color="! empty($outputs['recording']) ? 'warning' : 'gray'"
                            wire:click="sendCommand('{{ ! empty($outputs['recording']) ? 'StopRecord' : 'StartRecord' }}')"
                        >
                            {{ ! empty($outputs['recording']) ? 'Stop Record' : 'Start Record' }}
                        </x-filament::button>

                        <x-filament::button color="gray" wire:click="sendCommand('ToggleRecordPause')">
                            {{ ! empty($outputs['recordingPaused']) ? 'Resume Rec' : 'Pause Rec' }}
                        </x-filament::button>

                        <x-filament::button
                            :color="! empty($outputs['virtualCam']) ? 'success' : 'gray'"
                            wire:click="sendCommand('ToggleVirtualCam')"
                        >
                            Virtual Cam
                        </x-filament::button>

                        <x-filament::button color="gray" wire:click="sendCommand('SaveReplayBuffer')">
                            Save Replay
                        </x-filament::button>

                        <x-filament::button
                            :color="$studio->isStudioModeEnabled() ? 'warning' : 'gray'"
                            wire:click="toggleStudioMode"
                        >
                            Studio Mode
                        </x-filament::button>

                        @if ($studio->isStudioModeEnabled())
                            <x-filament::button color="primary" wire:click="sendCommand('TriggerStudioModeTransition')">
                                Transition → Live
                            </x-filament::button>
                        @endif
                    </div>
                </x-filament::section>

                <x-filament::section heading="1-Click Broadcast Macros">
                    <div class="flex flex-wrap gap-2">
                        @forelse ($macros as $macro)
                            <x-filament::button color="warning" wire:click="runMacro({{ $macro->id }})">
                                {{ $macro->name }}
                            </x-filament::button>
                        @empty
                            <p class="text-sm text-gray-500">No macros created yet.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            {{-- Scenes & Sources --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-filament::section heading="Scenes">
                    <div class="mb-4 flex gap-2">
                        <input
                            type="text"
                            wire:model="newSceneName"
                            placeholder="New scene name..."
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                        >
                        <x-filament::button size="sm" wire:click="createNewScene">Create Scene</x-filament::button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @forelse ($scenes as $scene)
                            @php
                                $sName = $scene['sceneName'] ?? '';
                                $isProgram = $studio->getCurrentProgramScene() === $sName;
                                $isPreview = $studio->getCurrentPreviewScene() === $sName;
                            @endphp
                            <x-filament::button
                                :color="$isProgram ? 'danger' : ($isPreview ? 'warning' : 'gray')"
                                wire:click="switchScene('{{ addslashes($sName) }}')"
                            >
                                {{ $sName }}
                            </x-filament::button>
                        @empty
                            <p class="col-span-full text-sm text-gray-500">Waiting for obs-agent connection to sync OBS scenes.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section heading="Active Scene Sources & Visibility">
                    <div class="space-y-2">
                        @forelse ($sceneItems as $item)
                            @php
                                $itemId = (int) ($item['sceneItemId'] ?? 0);
                                $enabled = (bool) ($item['sceneItemEnabled'] ?? false);
                                $sourceName = (string) ($item['sourceName'] ?? '');
                                $kind = (string) ($item['inputKind'] ?? '');
                            @endphp
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-800">
                                <div>
                                    <div class="text-sm font-medium">{{ $sourceName }}</div>
                                    <div class="text-xs text-gray-500">{{ $kind ?: 'source' }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($kind === 'browser_source')
                                        <x-filament::button size="xs" color="gray" wire:click="refreshBrowserSource('{{ addslashes($sourceName) }}')">
                                            Refresh
                                        </x-filament::button>
                                    @endif
                                    <x-filament::button
                                        size="xs"
                                        :color="$enabled ? 'success' : 'gray'"
                                        wire:click="toggleSceneItem({{ $itemId }}, {{ $enabled ? 'false' : 'true' }})"
                                    >
                                        {{ $enabled ? 'Visible' : 'Hidden' }}
                                    </x-filament::button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No active scene items synced yet.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            {{-- Audio Mixer & Live Screen Overlays --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-filament::section heading="Audio Mixer">
                    <div class="space-y-3">
                        @forelse ($audioInputs as $input)
                            @php
                                $inName = (string) ($input['inputName'] ?? '');
                                $muted = (bool) ($input['inputMuted'] ?? false);
                                $db = round((float) ($input['inputVolumeDb'] ?? 0), 1);
                            @endphp
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold">{{ $inName }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ $db }} dB</span>
                                        <x-filament::button
                                            size="xs"
                                            :color="$muted ? 'danger' : 'success'"
                                            wire:click="toggleInputMute('{{ addslashes($inName) }}', {{ $muted ? 'false' : 'true' }})"
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
                                    class="mt-2 w-full"
                                    x-on:change="$wire.setInputVolumeDb('{{ addslashes($inName) }}', parseFloat($event.target.value))"
                                >
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No OBS audio inputs detected yet.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section heading="Live Screen Overlay Controller (Reverb)">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <select
                                wire:model.live="selectedScreenId"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"
                            >
                                @foreach ($screens as $scr)
                                    <option value="{{ $scr->id }}">{{ $scr->name }} ({{ $scr->template_type }})</option>
                                @endforeach
                            </select>
                            @if ($selectedScreen)
                                <x-filament::button size="sm" color="gray" tag="a" :href="$selectedScreen->getOverlayUrl()" target="_blank">
                                    Open Overlay
                                </x-filament::button>
                                <x-filament::button size="sm" color="warning" wire:click="pushSelectedScreenToObs">
                                    Push to OBS
                                </x-filament::button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <input type="text" wire:model="liveGuestName" placeholder="Lower-third name" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                            <input type="text" wire:model="liveGuestRole" placeholder="Role / subtitle" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-filament::button size="sm" color="primary" wire:click="updateLiveOverlay(true)">
                                Show &amp; Update Lower Third
                            </x-filament::button>
                            <x-filament::button size="sm" color="gray" wire:click="updateLiveOverlay(false)">
                                Hide Lower Third
                            </x-filament::button>
                        </div>

                        <div class="border-t border-gray-200 pt-3 dark:border-gray-800">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <input type="text" wire:model="liveAlertTitle" placeholder="Alert title" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                                <input type="text" wire:model="liveAlertMessage" placeholder="Pop-up alert message..." class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <x-filament::button size="sm" color="warning" wire:click="triggerLiveAlert(true)">
                                    Push Alert Toast
                                </x-filament::button>
                                <x-filament::button size="sm" color="gray" wire:click="triggerLiveAlert(false)">
                                    Dismiss Alert
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Recent Command Audit Log --}}
            <x-filament::section heading="Recent OBS Command Log">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800">
                                <th class="py-2 pr-4">Command</th>
                                <th class="py-2 pr-4">Status</th>
                                <th class="py-2 pr-4">Error</th>
                                <th class="py-2">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="border-b border-gray-100 dark:border-gray-900">
                                    <td class="py-2 pr-4 font-mono text-xs">{{ $log->request_type }}</td>
                                    <td class="py-2 pr-4">
                                        <x-filament::badge :color="match($log->status) { 'succeeded' => 'success', 'failed' => 'danger', default => 'warning' }">
                                            {{ strtoupper($log->status) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="py-2 pr-4 text-xs text-red-500">{{ $log->error_message ?? '—' }}</td>
                                    <td class="py-2 text-xs text-gray-500">{{ $log->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-sm text-gray-500">No commands dispatched yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
