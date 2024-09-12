<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamPlayer;
use App\Models\Tournament;
use App\Models\MatchMaking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    public function index(Tournament $id)
    {
        return response()->json($id->with(['game', 'teams', 'matchMakings'])->get());
    }

    public function matchTeams()
    {
        $id = MatchMaking::where('active', true)->first();
        $winner = $id->winning_team;
        if ($winner != null) {
            if ($winner == $id->team_a) {
                $teama = Team::where('id', $id->teamA->id)->first();
                $teamb = Team::where('id', $id->teamB->id)->first();
            } else {
                $teama = Team::where('id', $id->teamB->id)->first();
                $teamb = Team::where('id', $id->teamA->id)->first();
            }
        } else {
            $teama = Team::where('id', $id->teamA->id)->first();
            $teamb = Team::where('id', $id->teamB->id)->first();
        }
        $teamAmvp = TeamPlayer::where('team_id', $teama->id)->where('is_mvp', true)->first();
        $teamBmvp = TeamPlayer::where('team_id', $teamb->id)->where('is_mvp', true)->first();
        return response()->json([
            'Team A' => [
                'name' => $teama->name,
                'logo' => $teama->team_logo_url,
                'mvp' => $teamAmvp
            ],
            'Team B' => [
                'name' => $teamb->name,
                'logo' => $teamb->team_logo_url,
                'mvp' => $teamBmvp
            ]
        ], 200);
    }
}
