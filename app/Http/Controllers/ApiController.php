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
    public function index(Tournament $id) {
        return response()->json($id->with(['game','teams','matchMakings'])->get());
    }

    public function matchTeams(MatchMaking $id) {
        $teama = Team::where('id', $id->teamA->id)->first();
        $teamb = Team::where('id', $id->teamB->id)->first();
        $teamAmvp = TeamPlayer::where('team_id', $id->teamA->id)->where('is_mvp', true)->first();
        $teamBmvp = TeamPlayer::where('team_id', $id->teamB->id)->where('is_mvp', true)->first();
        return response()->json([
            'Team A' => [
                'name' => $teama->name,
                'mvp' => $teamAmvp
            ],
            'Team B' => [
                'name' => $teamb->name,
                'mvp' => $teamBmvp
            ]
        ],200);
    }

    public function mvpAimage(MatchMaking $id) {
        $teamAmvp = TeamPlayer::where('team_id', $id->teamA->id)->where('is_mvp', true)->first();
        $image = Storage::get($teamAmvp->hero->hero_image_path);
        return response($image, 200, [
            'Content-Type' => 'image/jpeg'
        ]);
    }
}
