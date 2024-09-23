<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeams;
use App\Models\MatchMaking;
use App\Models\TournamentTheme;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class DownloadController extends Controller
{

    public function groupStatic() {
        $group = Group::where('active', true)->firstOrFail();
        $groupTeams = GroupTeams::where('group_id', $group->id)->orderBy('pts', 'desc')->get();
        $tournamentTheme = TournamentTheme::where('tournament_id', $group->tournament_id)->first();
        $primary = $tournamentTheme->primary_color;
        $secondary = $tournamentTheme->secondary_color;
        $accent = $tournamentTheme->ascent_color;

        return view('screens.static.group', compact('group','groupTeams', 'primary', 'secondary', 'accent'));
    }

    public function Match1080Static() {
        $match = MatchMaking::where('active', true)->with(['teamA', 'teamB'])->firstOrFail();
        $tournamentTheme = TournamentTheme::where('tournament_id', $match->tournament_id)->first();
        $primary = $tournamentTheme->primary_color;
        $secondary = $tournamentTheme->secondary_color;
        $accent = $tournamentTheme->ascent_color;
        $leftWin = '';
        $rightWin = '';
        if($match->winning_team == $match->team_a) {
            $leftWin = '#4CAF50';
            $rightWin = '#F44336';
        } else {
            $leftWin = '#F44336';
            $rightWin = '#4CAF50';
        }

        return view('screens.static.match_1080p', compact('match', 'primary', 'secondary', 'accent', 'leftWin', 'rightWin'));

    }
}
