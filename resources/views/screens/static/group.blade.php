<x-screen>
    <div class="flex gap-32 items-center justify-center h-full relative" style="background: url({{ $group->tournament->asset!= null && isset($group->tournament->asset->background) ? asset('storage/' . $group->tournament->asset->background) : '' }});">
        <div class="absolute top-10 left-16 flex gap-6 items-center">
            @if ($group->tournament->asset != null && isset($group->tournament->asset->tournament_logo)) 
                <img src="{{ asset('storage/'. $group->tournament->asset->tournament_logo) }}" alt="" class="max-w-[15rem]">
            @endif
            <div class="flex flex-col text-white font-bold">
                <h1 class="text-6xl py-6 px-8 bg-pink-600">{{ $group->tournament->name }}</h1>
                <p class="text-4xl py-4 px-8 bg-gray-800 w-max">Group Standing</p>
            </div>
        </div>
        <table class="border-separate border-spacing-y-[1rem] border-spacing-x-0 max-w-xl w-full">
            <thead class="bg-pink-700 text-4xl font-black text-white -skew-x-12">
                <th colspan="2" class="py-6">
                    <p class="skew-x-12">Team</p>
                </th>
                <th class="w-sm px-8 text-center skew-x-12">P</th>
                <th class="w-sm px-8 text-center skew-x-12">W</th>
                <th class="w-sm px-8 text-center skew-x-12">L</th>
                <th class="w-sm px-8 text-center skew-x-12">D</th>
                <th class="w-sm px-8 text-center skew-x-12">F</th>
                <th class="w-sm px-8 text-center skew-x-12 pr-12">Pts</th>

            </thead>
            @foreach ($groupTeams as $team)
            <tr class=" {{ $team->qualified == true ? "bg-gradient-to-tl from-lime-500/60 to-lime-200" : "bg-gradient-to-tl from-slate-400/60 to-slate-100" }} bg-white text-4xl font-bold -skew-x-12 transform">
                <td class="relative !bg-transparent">
                    <div class="px-8 py-4 bg-pink-700 text-white absolute top-1/2 transform -translate-y-1/2 -left-10 shadow-[10px_10px_12px_-2px_rgb(0_0_0_/_0.5)]">
                        <p class="skew-x-12 font-bold text-4xl">{{ $loop->iteration }}</p>
                    </div>
                </td>
                <td class="skew-x-12 pl-10 pr-6 py-4 min-w-[34rem] flex gap-4 items-center">
                    @if ($team->team->team_logo_url)
                    <img src="{{ $team->team->team_logo_url }}" alt="" class="w-[5rem] object-cover ml-8">
                    @else
                    <i class="fas fa-users w-[5rem] ml-8 p-2 justify-center aspect-square flex items-center text-[3.5rem]"></i>
                    @endif
                    <p>{{ $team->team->name }}</p>
                </td>
                <td class="skew-x-12 text-center">{{ $team->team->p }}</td>
                <td class="skew-x-12 text-center">{{ $team->team->w }}</td>
                <td class="skew-x-12 text-center">{{ $team->team->l }}</td>
                <td class="skew-x-12 text-center">{{ $team->team->d }}</td>
                <td class="skew-x-12 text-center">{{ $team->team->f }}</td>
                <td class="skew-x-12 text-center">{{ $team->team->pts }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</x-screen>
