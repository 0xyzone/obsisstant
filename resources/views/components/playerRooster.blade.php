
<div class="flex gap-4 bg-gray-800/50">
    <div class="relative">
        <img src=" {{ App\Models\Hero::where('id', $playerData->hero)->first()->hero_image_url ?? "" }}" class="w-32" alt="{{ App\Models\Hero::where('id', $playerData->hero)->first()->name }}">
        <p class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-full text-center" style="background-color: {{ $getRecord()->tournament->themes[0]->primary_color }};">{{ App\Models\Hero::where('id', $playerData->hero)->first()->name }}</p>
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