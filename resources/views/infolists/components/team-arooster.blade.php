<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
    $state = json_decode($getState()); // Decode the JSON string into an array
    $teamAmp = $state->teamA->mp ?? 'No MP available';
    @endphp
    <div class="w-full antialiased">
        <div class="flex gap-4">
            <div class="w-1/2">
                <div class="font-bold text-white flex justify-between text-2xl" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">
                    <h1 class="py-2 pl-4">{{ $state->teamA->name }}</h1>
                    <p class="h-full aspect-square text-center py-2 px-4" style="background-color: {{ $getRecord()->tournament->themes[0]->secondary_color }};">{{ $state->teamA->mp }}</p>
                </div>
                <div class="grid gap-2 py-4">
                    @foreach ($state->teamA->rooster as $player)
                    @php
                    $playerData = reset($player); // Get the inner player data object (e.g., p1, p2)

                    @endphp
                    <div class="flex gap-4 bg-gray-800/50">
                        <div class="relative">
                            <img src=" {{ App\Models\Hero::where('name', $playerData->hero)->first()->hero_image_url ?? "" }}" class="w-32" alt="{{ $playerData->hero }}">
                            <p class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-full text-center" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">{{ $playerData->hero }}</p>
                            @if ($playerData->is_mvp == true)
                                <p class="absolute top-0 right-0 px-2 bg-amber-400 text-gray-900 text-xs font-bold">MVP</p>
                            @endif
                        </div>
                        <div class="grid grid-cols-4 gap-2 h-auto place-content-center">
                            <p class="col-span-3">
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Player:</span><br>
                                {{ $playerData->name }}
                            </p>
                            <p class="">
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">K/D/A:</span><br>
                                {{ $playerData->k ?? 'n/a ' }}/{{ $playerData->d ?? ' n/a ' }}/{{ $playerData->a ?? ' n/a' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Hero Damage:</span><br>
                                {{ $playerData->hero_damage ?? 'N/A' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Turret Damage:</span><br>
                                {{ $playerData->turret_damage ?? 'N/A' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Damage Taken:</span><br>
                                {{ $playerData->damage_taken ?? 'N/A' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Fight Participation:</span><br>
                                {{ $playerData->fight_participation ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="w-1/2">
                <div class="font-bold text-white flex justify-between text-2xl" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">
                    <p class="h-full aspect-square text-center py-2 px-4" style="background-color: {{ $getRecord()->tournament->themes[0]->secondary_color }};">{{ $state->teamB->mp }}</p>
                    <h1 class="py-2 pr-4">{{ $state->teamB->name }}</h1>
                </div>
                <div class="grid gap-2 py-4">
                    @foreach ($state->teamB->rooster as $player)
                    @php
                    $playerData = reset($player); // Get the inner player data object (e.g., p1, p2)

                    @endphp
                    <div class="flex gap-4 bg-gray-800/50">
                        <div class="relative">
                            <img src=" {{ App\Models\Hero::where('name', $playerData->hero)->first()->hero_image_url ?? "" }}" class="w-32" alt="{{ $playerData->hero }}">
                            <p class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-full text-center" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">{{ $playerData->hero }}</p>
                            @if ($playerData->is_mvp == true)
                                <p class="absolute top-0 right-0 px-2 bg-amber-400 text-gray-900 text-xs font-bold">MVP</p>
                            @endif
                        </div>
                        <div class="grid grid-cols-4 gap-2 h-auto place-content-center">
                            <p class="col-span-3">
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Player:</span><br>
                                {{ $playerData->name }}
                            </p>
                            <p class="">
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">K/D/A:</span><br>
                                {{ $playerData->k ?? 'n/a ' }}/{{ $playerData->d ?? ' n/a ' }}/{{ $playerData->a ?? ' n/a' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Hero Damage:</span><br>
                                {{ $playerData->hero_damage ?? 'N/A' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Turret Damage:</span><br>
                                {{ $playerData->turret_damage ?? 'N/A' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Damage Taken:</span><br>
                                {{ $playerData->damage_taken ?? 'N/A' }}
                            </p>
                            <p>
                                <span style="color: {{ $getRecord()->tournament->themes[0]->primary_color }};" class="text-xs">Fight Participation:</span><br>
                                {{ $playerData->fight_participation ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
