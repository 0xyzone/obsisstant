<x-screen1080>
    <div class="w-full h-full leading-none " style="background: url({{ $match->tournament->asset != null && isset($match->tournament->asset->background) ? asset('storage/' . $match->tournament->asset->background) : '' }});">
        <div class="flex gap-6 items-center pt-10 w-11/12 mx-auto">
            @if ($match->tournament->asset != null && isset($match->tournament->asset->tournament_logo))
            <img src="{{ asset('storage/'. $match->tournament->asset->tournament_logo) }}" alt="" class="max-w-[15rem]">
            @endif
            <div class="flex flex-col text-white font-bold w-full">
                <h1 class="text-4xl px-8 leading-none bg-pink-600 w-full align-top">{{ $match->tournament->name }}</h1>
                <div class="flex">
                    <p class="text-2xl py-4 px-8 bg-gray-800 w-max">Match Results</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 justify-center">
            <img src="{{ $match->teamA->team_logo_url }}" alt="{{ $match->teamA->name }}_logo" class="w-4/12 aspect-square object-cover rounded-3xl">
            <span class="text-9xl text-white font-bold">{{ $match->teamA_match_point }}</span>
            <span class="text-9xl text-white font-bold">:</span>
            <span class="text-9xl text-white font-bold">{{ $match->teamB_match_point }}</span>
            <img src="{{ $match->teamB->team_logo_url }}" alt="{{ $match->teamB->name }}_logo" class="w-4/12 aspect-square object-cover rounded-3xl">
        </div>

    </div>
</x-screen1080>