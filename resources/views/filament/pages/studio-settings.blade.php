<x-filament-panels::page>
    <div
        @if ($pendingPickerCommandUuid) wire:poll.600ms="checkNativePickerResult" @endif
        class="obs-deck-stack"
    >
        <x-filament::section
            heading="Native Desktop / Mac Media Storage"
            description="Choose the folder on your OBS computer (Mac/Windows/Linux) where uploaded Image Source files are stored so OBS Studio can load them directly."
        >
            <div class="obs-list-stack-md">
                <form wire:submit="save" class="obs-list-stack-md">
                    <div class="obs-inline-form">
                        <input
                            type="text"
                            wire:model="mediaStoragePath"
                            placeholder="~/Pictures/Obsisstant or /Users/yourname/Pictures/OBS"
                            class="obs-deck-input obs-inline-grow"
                            aria-label="Native Desktop Storage Path"
                        >
                        <x-filament::button
                            type="button"
                            color="gray"
                            wire:click="openNativeFolderPicker"
                        >
                            @if ($pendingPickerCommandUuid)
                                Waiting for Finder...
                            @else
                                Browse Desktop Folder...
                            @endif
                        </x-filament::button>
                        <x-filament::button type="submit" color="primary">
                            Save Location
                        </x-filament::button>
                    </div>

                    @error('mediaStoragePath')
                        <p style="margin: 0; font-size: 0.75rem; color: #f4a3a6;">{{ $message }}</p>
                    @enderror
                </form>

                <div>
                    <p class="obs-deck-meta" style="margin-block-end: 0.5rem;">
                        Quick Mac / Desktop presets:
                    </p>
                    <div class="obs-btn-row">
                        @foreach ($this->getFolderPresets() as $preset)
                            <x-filament::button
                                type="button"
                                size="xs"
                                :color="$mediaStoragePath === $preset['path'] ? 'primary' : 'gray'"
                                wire:click="applyPreset('{{ addslashes($preset['path']) }}')"
                            >
                                {{ $preset['label'] }} ({{ $preset['path'] }})
                            </x-filament::button>
                        @endforeach
                    </div>
                </div>

                @php
                    $studios = $this->getStudios();
                @endphp
                @if ($studios->isNotEmpty())
                    <div class="obs-deck-divider">
                        <label for="picker-studio-select" class="obs-deck-meta" style="display: block; margin-block-end: 0.375rem;">
                            Desktop Agent Used For Native Folder Browser:
                        </label>
                        <div class="obs-inline-form">
                            <select id="picker-studio-select" wire:model="selectedStudioId" class="obs-deck-input obs-deck-select">
                                @foreach ($studios as $studioItem)
                                    <option value="{{ $studioItem->id }}">
                                        {{ $studioItem->name }} ({{ $studioItem->isOnline() ? 'Online · Native Finder ready' : 'Offline' }})
                                    </option>
                                @endforeach
                            </select>
                            <x-filament::button
                                tag="a"
                                size="sm"
                                color="gray"
                                :href="route('studio.index')"
                            >
                                Open Studio Control Center
                            </x-filament::button>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
