<x-screen class="bg-lime-500">
    <div class="bg-red-500">
        @foreach ($groupTeams as $team)
        <div class="grid grid-cols-8 max-w-4xl mx-auto">
            <p>{{ $loop->iteration }}</p>
            <p>{{ $team->team->name }}</p>
            <p>{{ $team->team->p }}</p>
            <p>{{ $team->team->w }}</p>
            <p>{{ $team->team->l }}</p>
            <p>{{ $team->team->d }}</p>
            <p>{{ $team->team->f }}</p>
            <p>{{ $team->team->pts }}</p>
        </div>
        @endforeach

    </div>
</x-screen>
