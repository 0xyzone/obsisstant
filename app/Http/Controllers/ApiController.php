<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Group;
use App\Models\GroupTeams;
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
        $group = Group::where('active', true)->first();
        if($group){
            $groupTeams = GroupTeams::where('group_id', $group->id)->with('team')->orderBy('pts', 'desc')->get();
        }else{
            $groupTeams = null;
        }
        $winner = $id->winning_team;
        if ($winner != null) {
            if ($winner == $id->team_a) {
                $matchTeam = Team::where('id', $id->teamA->id)->first();
                $matchMvp = TeamPlayer::where('id', $matchTeam->id)->where('is_mvp', true)->first();
            } else {
                $matchTeam = Team::where('id', $id->teamA->id)->first();
                $matchMvp = TeamPlayer::where('id', $matchTeam->id)->where('is_mvp', true)->first();
            }
        } else {
            $matchMvp = [];
        }
        $teama = Team::where('id', $id->teamA->id)->first();
        $teamb = Team::where('id', $id->teamA->id)->first();
        $teamAmvp = TeamPlayer::where('team_id', $teama->id)->where('is_mvp', true)->first();
        $teamArooster = TeamPlayer::where('team_id', $teama->id)->where('is_playing', true)->get();
        $teamBmvp = TeamPlayer::where('team_id', $teamb->id)->where('is_mvp', true)->first();
        $teamBrooster = TeamPlayer::where('team_id', $teamb->id)->where('is_playing', true)->get();
        return response()->json([
            'Team A' => [
                'name' => $teama->name,
                'logo' => $teama->team_logo_url,
                'mvp' => $teamAmvp,
                'rooster' => $teamArooster,
            ],
            'Team B' => [
                'name' => $teamb->name,
                'logo' => $teamb->team_logo_url,
                'mvp' => $teamBmvp,
                'rooster' => $teamBrooster,
            ],
            'MatchMvp' => $matchMvp,
            'Group Name' => $group->name ?? null,
            'Group Standing' => $groupTeams
        ], 200);
    }
}
