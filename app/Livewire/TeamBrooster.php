<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Component;
use App\Models\TeamPlayer;
use App\Models\MatchMaking;

class TeamBrooster extends Component
{
    public $match;
    public $team;
    public $rooster;

    public function mount()
    {
        $this->match = MatchMaking::where('active', true)->first();
        $this->team = Team::where('id', $this->match->team_b)->first();
        $this->rooster = TeamPlayer::where('team_id', $this->match->team_b)->get();
    }
    public function render()
    {
        return view('livewire.team-brooster')->layout('layouts.screen');
    }
}
