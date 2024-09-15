<div class="flex gap-32 items-center justify-center h-full">
    <img src="{{ $team->team_logo_url ?? "" }}" alt="{{ $team->name }}_logo" class="max-w-sm rounded-2xl drop-shadow-[15px_15px_18px_-2px_rgb(0_0_0_/_0.3)] animate-fade-in-right opacity-0" style="animation-delay: 0.8s;">
    <table class="border-separate border-spacing-y-[0.75rem] border-spacing-x-0 max-w-xl w-full">
            <thead>
                <th colspan="2" class="bg-pink-700 !-skew-x-12 py-6 text-4xl font-black animate-fade-in-top opacity-0" style="animation-delay: 1s;">
                    <p class="skew-x-12 text-white">{{ $team->name }}</p>
                </th>
            </thead>
            @foreach ($rooster as $player)
            <tr class="animate-rise-up opacity-0" style="animation-delay: {{ 1.2 + $loop->iteration * 0.2 }}s;">
                <td class="relative !bg-transparent">
                    <div class="px-4 py-2 bg-pink-500 -skew-x-12 absolute top-1/2 transform -translate-y-1/2 -left-5 shadow-[5px_5px_8px_-2px_rgb(0_0_0_/_0.5)]">
                        <p class="skew-x-12 font-bold text-xl">{{ $loop->iteration }}</p>
                    </div>
                </td>
                <td class="pl-10 pr-6 py-6 rounded-xl bg-gradient-to-tl from-slate-400/60 to-slate-100 bg-white text-4xl font-bold">{{ $player->name }}</td>
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
                transform: translateY(-20px);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes riseUpFadeIn {
            0% {
                transform: translateY(20px);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
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
