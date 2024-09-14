<x-screen class="bg-lime-500">
    <div class="">
        <table class="mx-auto text-center border-separate border-spacing-y-[0.75rem] border-spacing-x-0">
            <thead>
                <th></th>
                <th>Team</th>
                <th class="w-sm">P</th>
                <th class="w-sm">W</th>
                <th class="w-sm">L</th>
                <th class="w-sm">D</th>
                <th class="w-sm">F</th>
                <th class="w-sm">Pts</th>
            </thead>
            <tbody>
                @foreach ($groupTeams as $team)
                <tr class="odd:bg-gray-200 even:bg-gray-300 bg-white">
                    <td class="text-center relative"><div class=" px-4 py-2 bg-orange-500 -skew-x-12 absolute top-1/2 transform -translate-y-1/2 -left-5">{{ $loop->iteration }}</div></td>
                    <td class="gap-2 w-96"><div class="flex gap-2 h-full items-center pl-10"><img src="{{ $team->team->team_logo_url }}" class="aspect-square w-6" alt="">{{ $team->team->name }}</div></td>
                    <td class="text-center w-10 py-6">{{ $team->team->p }}</td>
                    <td class="text-center w-10">{{ $team->team->w }}</td>
                    <td class="text-center w-10">{{ $team->team->l }}</td>
                    <td class="text-center w-10">{{ $team->team->d }}</td>
                    <td class="text-center w-10">{{ $team->team->f }}</td>
                    <td class="text-center w-10">{{ $team->team->pts }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div>
        </div>

    </div>
</x-screen>
