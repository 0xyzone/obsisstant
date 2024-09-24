<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
    $state = json_decode($getState()); // Decode the JSON string into an array
    $teamAmp = $state->teamA->mp ?? 'No MP available';
    @endphp
    <div class="w-full">
        <div class="flex gap-4">
            <div class="w-1/2">
                <div class="px-2 py-4 font-bold text-white" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">
                    <h1>{{ $state->teamA->name }}</h1>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($state->teamA->rooster as $player)
                    @php
                    $playerData = reset($player); // Get the inner player data object (e.g., p1, p2)

                    @endphp
                    <div class="py-2 bg-gray-200">
                        <strong>Player Name:</strong> {{ $playerData->name }}<br>
                        <strong>Hero:</strong> {{ $playerData->hero }}<br>
                        <strong>Is MVP:</strong> {{ $playerData->is_mvp }}<br>
                        <strong>Fight Participation:</strong> {{ $playerData->fight_participation ?? 'N/A' }}<br>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="w-1/2">
                <div class="px-2 py-4 font-bold text-white" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">
                    <h1>{{ $state->teamB->name }}</h1>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($state->teamB->rooster as $player)
                    @php
                    $playerData = reset($player); // Get the inner player data object (e.g., p1, p2)

                    @endphp
                    <div class="py-2 bg-red-500">
                        <strong>Player Name:</strong> {{ $playerData->name }}<br>
                        <strong>Hero:</strong> {{ $playerData->hero }}<br>
                        <strong>Is MVP:</strong> {{ $playerData->is_mvp }}<br>
                        <strong>Fight Participation:</strong> {{ $playerData->fight_participation ?? 'N/A' }}<br>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
