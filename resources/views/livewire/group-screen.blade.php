<x-screen class="bg-lime-500">
    <div class="bg-red-500">
        <div class="grid grid-cols-8 max-w-5xl mx-auto text-center">
            <p>Position</p>
            <p>Team</p>
            <p>P</p>
            <p>W</p>
            <p>L</p>
            <p>D</p>
            <p>F</p>
            <p>Pts</p>
        </div>
        @foreach ($groupTeams as $team)
        <div class="grid grid-cols-8 max-w-5xl mx-auto odd:bg-gray-200 even:bg-gray-300">
            <div class="text-center">{{ $loop->iteration }}</div>
            <div class="w-full"><img src="{{ $team->team->team_logo_url }}" class="aspect-square w-6" alt="">{{ $team->team->name }}</div>
            <div class="text-center">{{ $team->team->p }}</div>
            <div class="text-center">{{ $team->team->w }}</div>
            <div class="text-center">{{ $team->team->l }}</div>
            <div class="text-center">{{ $team->team->d }}</div>
            <div class="text-center">{{ $team->team->f }}</div>
            <div class="text-center">{{ $team->team->pts }}</div>
        </div>
        @endforeach

    </div>
</x-screen>
