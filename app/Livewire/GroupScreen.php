<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\GroupTeams;
use Livewire\Component;

class GroupScreen extends Component
{
    public $group;
    public $groupTeams;

    public function mount() {
        $this->group = Group::where('active', true)->firstOrFail();
        $this->groupTeams = GroupTeams::where('group_id', $this->group->id)->orderBy('pts', 'desc')->get();
    }
    public function render()
    {
        return view('livewire.group-screen')->layout('layouts.screen');
    }
}
