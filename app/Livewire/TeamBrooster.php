<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Component;
use App\Models\TeamPlayer;
use App\Models\MatchMaking;
use App\Models\TournamentTheme;

class TeamBrooster extends Component
{
    public $match;
    public $team;
    public $rooster;
    public $primary;
    public $secondary;
    public $accent;

    public function mount()
    {
        $this->match = MatchMaking::where('active', true)->first();
        $this->team = Team::where('id', $this->match->team_b)->first();
        $this->rooster = TeamPlayer::where('team_id', $this->match->team_b)->where('is_playing', true)->get();
        
        $tournamentTheme = TournamentTheme::where('tournament_id', $this->match->tournament_id)->first();
        $this->primary = $tournamentTheme->primary_color;
        $this->secondary = $tournamentTheme->secondary_color;
        $this->accent = $tournamentTheme->ascent_color;
    }
    public function render()
    {
        return view('livewire.team-brooster')->layout('layouts.screen');
    }
}
