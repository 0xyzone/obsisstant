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
                    <x-playerRooster :playerData=$playerData></x-playerRooster>
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
                    <x-playerRooster :playerData=$playerData></x-playerRooster>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
