<x-screen1080>
    <div class="w-full h-full" style="background: url({{ $match->tournament->asset != null && isset($match->tournament->asset->background) ? asset('storage/' . $match->tournament->asset->background) : '' }});">
        <div class="flex gap-6 items-center pt-10 w-11/12 mx-auto">
            @if ($match->tournament->asset != null && isset($match->tournament->asset->tournament_logo))
            <img src="{{ asset('storage/'. $match->tournament->asset->tournament_logo) }}" alt="" class="max-w-[15rem]">
            @endif
            <div class="flex flex-col text-white font-bold w-full">
                <h1 class="text-4xl px-8 py-4 leading-none w-full align-top" style="background-color: {{ $primary }};">{{ $match->tournament->name }}</h1>
                <div class="flex">
                    <p class="text-2xl py-4 px-8 w-max" style="background-color: {{ $secondary }};">Match Results</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 justify-center mt-10">
            <div class="w-4/12 flex flex-col items-center border-4 rounded-2xl overflow-hidden shrink-0" style="border-color: {{$leftWin}};">
                <img src="{{ $match->teamA->team_logo_url }}" alt="{{ $match->teamA->name }}_logo" class="aspect-square object-contain">
                <span class="text-xl text-center text-white font-bold px-8 py-2 w-full" style="background-color: {{ $leftWin . 'cc' }};">{{ $match->teamA->name }}</span>
            </div>
            <span class="text-5xl text-white font-bold px-8 py-2 rounded-2xl" style="background-color: {{ $leftWin . 'cc' }};">{{ $match->teamA_match_point }}</span>
            <span class="text-5xl text-white font-bold mb-2">:</span>
            <span class="text-5xl text-white font-bold px-8 py-2 rounded-2xl" style="background-color: {{ $rightWin . 'cc' }};">{{ $match->teamB_match_point }}</span>
            <div class="w-4/12 flex flex-col items-center border-4 rounded-2xl overflow-hidden shrink-0" style="border-color: {{$rightWin}};">
                <img src="{{ $match->teamB->team_logo_url }}" alt="{{ $match->teamB->name }}_logo" class="aspect-square object-contain">
                <span class="text-xl text-center text-white font-bold px-8 py-2 w-full" style="background-color: {{ $rightWin . 'cc' }};">{{ $match->teamB->name }}</span>
            </div>
        </div>

    </div>
</x-screen1080>
