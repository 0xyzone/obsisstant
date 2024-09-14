<?php

namespace App\Livewire;

use App\Models\Team;
use App\Models\TeamPlayer;
use Livewire\Component;
use App\Models\MatchMaking;

class TeamArooster extends Component
{
    public $match;
    public $team;
    public $rooster;

    public function mount()
    {
        $this->match = MatchMaking::where('active', true)->first();
        $this->team = Team::where('id', $this->match->team_a)->first();
        $this->rooster = TeamPlayer::where('team_id', $this->match->team_a)->where('is_playing', true)->get();
    }
    public function render()
    {
        return view('livewire.team-arooster')->layout('layouts.screen');
    }
}
