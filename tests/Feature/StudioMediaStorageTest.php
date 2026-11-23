<?php

namespace Tests\Feature;

use App\Events\ObsCommandDispatched;
use App\Filament\Pages\StudioSettings;
use App\Models\ObsCommandLog;
use App\Models\ObsStudio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StudioMediaStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_studio_settings_saves_user_media_storage_path_and_supports_native_folder_picker(): void
    {
        Event::fake([ObsCommandDispatched::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create([
            'name' => 'Mac Rig',
            'live_state' => [
                'agentHost' => [
                    'platform' => 'darwin',
                    'homeDir' => '/Users/streamer',
                    'defaultMediaDir' => '/Users/streamer/Pictures/Obsisstant',
                ],
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(StudioSettings::class)
            ->assertOk()
            ->assertSee('Native Desktop / Mac Media Storage')
            ->assertSee('/Users/streamer/Pictures/Obsisstant')
            ->set('mediaStoragePath', '/Users/streamer/Desktop/OBS Assets')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('/Users/streamer/Desktop/OBS Assets', $user->media_storage_path);

        $component = Livewire::test(StudioSettings::class)
            ->call('openNativeFolderPicker')
            ->assertHasNoErrors();

        $pendingUuid = $component->get('pendingPickerCommandUuid');
        $this->assertNotNull($pendingUuid);

        $log = ObsCommandLog::query()->where('command_uuid', $pendingUuid)->firstOrFail();
        $this->assertSame('obsisstant.PickLocalDirectory', $log->request_type);

        $log->update([
            'status' => 'succeeded',
            'response_data' => ['path' => '/Users/streamer/Movies/StreamGraphics', 'cancelled' => false],
        ]);

        $component->call('checkNativePickerResult');

        $user->refresh();
        $this->assertSame('/Users/streamer/Movies/StreamGraphics', $user->media_storage_path);
        $this->assertNull($component->get('pendingPickerCommandUuid'));
    }

    public function test_studio_media_upload_stages_for_online_agent_and_enforces_user_isolation(): void
    {
        Event::fake([ObsCommandDispatched::class]);

        $owner = User::factory()->create(['media_storage_path' => '~/Desktop/OBS Images']);
        $intruder = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($owner)->create();
        $intruderStudio = ObsStudio::factory()->online()->for($intruder)->create();

        $pngFile = UploadedFile::fake()->image('sponsor-logo.png', 320, 180);

        $this->actingAs($intruder)
            ->postJson(route('studio.media-upload', ['studio' => $studio->slug]), [
                'image' => $pngFile,
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->postJson(route('studio.media-storage-path', ['studio' => $studio->slug]), [
                'path' => '~/Pictures/OBS Uploads',
            ])
            ->assertOk()
            ->assertJsonPath('media_storage_path', '~/Pictures/OBS Uploads');

        $response = $this->actingAs($owner)
            ->postJson(route('studio.media-upload', ['studio' => $studio->slug]), [
                'image' => UploadedFile::fake()->image('sponsor-logo.png', 320, 180),
            ])
            ->assertOk()
            ->assertJsonPath('mode', 'agent')
            ->assertJsonPath('target_dir', '~/Pictures/OBS Uploads');

        $uuid = (string) $response->json('command_uuid');
        $log = ObsCommandLog::query()->where('command_uuid', $uuid)->firstOrFail();
        $this->assertSame('obsisstant.SaveMediaFile', $log->request_type);
        $this->assertSame('~/Pictures/OBS Uploads', $log->request_data['targetDir']);
        $downloadUrl = (string) $log->request_data['downloadUrl'];
        $this->assertNotEmpty($downloadUrl);

        // Foreign agent cannot download owner's uploaded file
        $this->withToken($intruderStudio->agent_token)
            ->get($downloadUrl)
            ->assertForbidden();

        // Owner's agent downloads once, then single-use token is consumed
        $this->withToken($studio->agent_token)
            ->get($downloadUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->withToken($studio->agent_token)
            ->get($downloadUrl)
            ->assertNotFound();

        // Validation rejects non-image uploads
        $this->actingAs($owner)
            ->postJson(route('studio.media-upload', ['studio' => $studio->slug]), [
                'image' => UploadedFile::fake()->create('script.txt', 10, 'text/plain'),
            ])
            ->assertStatus(422);
    }

    public function test_offline_local_fallback_writes_uploaded_image_into_configured_directory(): void
    {
        $tempDir = sys_get_temp_dir().'/obsisstant-media-test-'.Str::random(6);
        File::ensureDirectoryExists($tempDir);

        try {
            $owner = User::factory()->create(['media_storage_path' => $tempDir]);
            $studio = ObsStudio::factory()->for($owner)->create(['status' => 'offline']);

            $res = $this->actingAs($owner)
                ->postJson(route('studio.media-upload', ['studio' => $studio->slug]), [
                    'image' => UploadedFile::fake()->image('badge.png', 64, 64),
                ])
                ->assertOk()
                ->assertJsonPath('mode', 'direct')
                ->assertJsonPath('target_dir', $tempDir);

            $savedFile = (string) $res->json('file');
            $this->assertStringStartsWith($tempDir, $savedFile);
            $this->assertFileExists($savedFile);
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    public function test_control_center_renders_image_upload_and_storage_settings_controls(): void
    {
        $user = User::factory()->create(['media_storage_path' => '~/Desktop/StreamOverlayAssets']);
        $studio = ObsStudio::factory()->online()->for($user)->create();

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('Storage Settings')
            ->assertSee('~/Desktop/StreamOverlayAssets', false)
            ->assertSee(json_encode(route('studio.media-upload', ['studio' => $studio->slug], false)), false)
            ->assertSee('obsisstant.PickLocalDirectory');
    }
}
