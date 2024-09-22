<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTeams;
use App\Models\MatchMaking;
use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;

class DownloadController extends Controller
{
    public function downloadImage()
    {
        $pathToImage = public_path('images/downloadable/group.png');
        return response()->download($pathToImage);
    }

    public function groupStatic() {
        $group = Group::where('active', true)->firstOrFail();
        $groupTeams = GroupTeams::where('group_id', $group->id)->orderBy('pts', 'desc')->get();

        return view('screens.static.group', compact('group','groupTeams'));
    }

    public function downloadGroupStatic() {
        
        $path = public_path('images/downloadable/output.jpg');
        $image = Browsershot::url(route('groupScreenStatic'))
            ->fullPage()
            ->setOption('executablePath', "C:\Program Files\Google\Chrome\Application\chrome.exe")
            ->screenshot();  // Path where the image will be saved
            return response($image)
            ->header('Content-Type', 'image/jpeg');
        // return response()->download($path)->deleteFileAfterSend(true);
    }

    public function Match1080Static() {
        $match = MatchMaking::where('active', true)->with(['teamA', 'teamB'])->firstOrFail();

        return view('screens.static.match_1080p', compact('match'));

    }
}
