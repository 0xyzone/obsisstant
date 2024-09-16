<?php

namespace App\Livewire;

use App\Models\Group;
use Livewire\Component;
use App\Models\GroupTeams;
use Spatie\Browsershot\Browsershot;

class GroupScreen extends Component
{
    public $group;
    public $groupTeams;

    public function mount() {
        $this->group = Group::where('active', true)->firstOrFail();
        $this->groupTeams = GroupTeams::where('group_id', $this->group->id)->orderBy('pts', 'desc')->get();
    }

    public function generateImage()
    {
        $html = view('livewire.group-screen', [
            'groupTeams' => $this->groupTeams,
            'group' => $this->group
        ])->layout('layouts.screen');

        // Path to save the image temporarily
        $pathToImage = 'images/something.png';

        // Use Browsershot to generate the image
        Browsershot::html($html)
            ->setScreenshotType('png') // Use 'jpeg' for JPG
            ->windowSize(1920, 1080)    // Adjust the size as needed
            ->save($pathToImage);

        // Return the image as a download response
        return response()->download($pathToImage, 'something.png')->deleteFileAfterSend(true);
    }
    public function render()
    {
        // Check if the 'download' query parameter is set to 'true'
        if (request()->query('download') === 'true') {
            return $this->generateImage();
        }
        return view('livewire.group-screen', [
            'groupTeams' => $this->groupTeams,
            'group' => $this->group
        ])->layout('layouts.screen');
    }
}
