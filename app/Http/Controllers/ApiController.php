<?php

namespace App\Http\Controllers;

use App\Models\MatchMaking;
use App\Models\Tournament;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function index(Tournament $id) {
        return response()->json($id->with(['game','teams','matchMakings'])->get());
    }

    public function matchTeams(MatchMaking $id) {
        return response()->json($id->with(['teamA', 'teamB', 'winner'])->get());
    }
}
