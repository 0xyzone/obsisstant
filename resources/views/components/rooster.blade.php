
<div class="flex flex-col gap-4 items-center">
    <img src="{{ $team->team_logo_url ?? "" }}" alt="{{ $team->name }}_logo" class="max-w-sm rounded-2xl shadow-[15px_15px_18px_-2px_rgb(0_0_0_/_0.3)]">
    <table class="mx-auto border-separate border-spacing-y-[0.75rem] border-spacing-x-0 max-w-2xl w-full">
        <tbody>
            <thead>
                <th colspan="2" class="bg-pink-700 -skew-x-12 py-6 text-4xl">
                    <p class="skew-x-12 text-white">{{ $team->name }}</p>
                </th>
            </thead>
            @foreach ($rooster as $player)
            <tr class="">
                <td class="relative !bg-transparent">
                    <div class=" px-4 py-2 bg-pink-500 -skew-x-12 absolute top-1/2 transform -translate-y-1/2 -left-5 shadow-[5px_5px_8px_-2px_rgb(0_0_0_/_0.5)]">{{ $loop->iteration }}</div>
                </td>
                <td class="pl-10 pr-6 py-6 rounded-xl bg-gradient-to-tl from-slate-400/60 to-slate-100 bg-white text-2xl font-bold">{{ $player->name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>