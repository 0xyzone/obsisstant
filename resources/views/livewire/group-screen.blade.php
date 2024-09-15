<div class="flex gap-32 items-center justify-center h-full">
    <table class="border-separate border-spacing-y-[1rem] border-spacing-x-0 max-w-xl w-full">
        <thead class="bg-pink-700 animate-fade-in-top opacity-0 text-4xl font-black text-white" style="animation-delay: 1s;">
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
        <tr class="animate-rise-up opacity-0 {{ $team->qualified == true ? "bg-gradient-to-tl from-lime-500/60 to-lime-200" : "bg-gradient-to-tl from-slate-400/60 to-slate-100" }} bg-white text-4xl font-bold skew-x-12 transform" style="animation-delay: {{ 1.2 + $loop->iteration * 0.3 }}s;">
            <td class="relative !bg-transparent">
                <div class="px-8 py-4 bg-pink-700 text-white absolute top-1/2 transform -translate-y-1/2 -left-10 shadow-[10px_10px_12px_-2px_rgb(0_0_0_/_0.5)]">
                    <p class="skew-x-12 font-bold text-4xl">{{ $loop->iteration }}</p>
                </div>
            </td>
            <td class="skew-x-12 pl-10 pr-6 py-4 min-w-[34rem] flex gap-4 items-center">
                @if ($team->team->team_logo_url)
                <img src="{{ $team->team->team_logo_url }}" alt="" class="w-[5rem] object-cover ml-8">
                @else
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-[5rem] ml-8">
                    <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0ZM15.75 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM2.25 9.75a3 3 0 1 1 6 0 3 3 0 0 1-6 0ZM6.31 15.117A6.745 6.745 0 0 1 12 12a6.745 6.745 0 0 1 6.709 7.498.75.75 0 0 1-.372.568A12.696 12.696 0 0 1 12 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 0 1-.372-.568 6.787 6.787 0 0 1 1.019-4.38Z" clip-rule="evenodd" />
                    <path d="M5.082 14.254a8.287 8.287 0 0 0-1.308 5.135 9.687 9.687 0 0 1-1.764-.44l-.115-.04a.563.563 0 0 1-.373-.487l-.01-.121a3.75 3.75 0 0 1 3.57-4.047ZM20.226 19.389a8.287 8.287 0 0 0-1.308-5.135 3.75 3.75 0 0 1 3.57 4.047l-.01.121a.563.563 0 0 1-.373.486l-.115.04c-.567.2-1.156.349-1.764.441Z" />
                </svg>
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

    <!-- Add this custom CSS -->
    <style>
        @keyframes fadeInRight {
            0% {
                transform: translateX(-50px);
                opacity: 0;
            }

            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeInTop {
            0% {
                transform: translateY(-20px) skew(-12deg);
                opacity: 0;
            }

            100% {
                transform: translateY(0) skew(-12deg);
                opacity: 1;
            }
        }

        @keyframes riseUpFadeIn {
            0% {
                transform: translateY(20px) skew(-12deg);
                opacity: 0;
            }

            100% {
                transform: translateY(0) skew(-12deg);
                opacity: 1;
            }
        }

        .animate-fade-in-right {
            animation: fadeInRight 0.75s ease-in-out forwards;
        }

        .animate-fade-in-top {
            animation: fadeInTop 0.75s ease-in-out forwards;
        }

        .animate-rise-up {
            animation: riseUpFadeIn 0.75s ease-in-out forwards;
        }

    </style>
</div>
