<?php

namespace Tests\Feature;

use App\Events\ObsCommandDispatched;
use App\Events\ObsPreviewFrameUpdated;
use App\Events\ObsScreenUpdated;
use App\Events\ObsStudioStateUpdated;
use App\Models\ObsCommandLog;
use App\Models\ObsMacro;
use App\Models\ObsScreen;
use App\Models\ObsStudio;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\ReverbStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class StudioControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ReverbStatusService::resetFake();

        parent::tearDown();
    }

    public function test_studio_control_center_requires_authentication_and_renders_workspace(): void
    {
        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $studio = ObsStudio::factory()->online()->create([
            'name' => 'Broadcast A',
            'live_state' => [
                'currentProgramScene' => 'Main Scene',
                'scenes' => [
                    ['sceneName' => 'Main Scene'],
                    ['sceneName' => 'Guest Slate'],
                ],
                'sceneItemsByScene' => [
                    'Main Scene' => [
                        [
                            'sceneItemId' => 3,
                            'sourceName' => 'Host Camera',
                            'inputKind' => 'macos_avcapture',
                            'sceneItemIndex' => 0,
                            'sceneItemEnabled' => true,
                            'sceneItemLocked' => false,
                            'sceneItemTransform' => [
                                'positionX' => 0,
                                'positionY' => 0,
                                'boundsWidth' => 1920,
                                'boundsHeight' => 1080,
                            ],
                        ],
                    ],
                ],
                'videoDimensions' => ['width' => 1920, 'height' => 1080],
                'inputKinds' => ['browser_source', 'image_source'],
                'allInputs' => [['inputName' => 'Host Camera', 'inputKind' => 'macos_avcapture']],
            ],
        ]);

        $this->get(route('studio.index'))->assertRedirect();

        $user = User::factory()->create();
        $versionTag = 'v'.ChangelogService::getCurrentVersion();

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('Obsisstant')
            ->assertSee('Broadcast A')
            ->assertSee($versionTag)
            ->assertSee('Reverb active')
            ->assertSee('favicon-local.svg', false)
            ->assertSee('scene-collection-select')
            ->assertSee('create-collection-dialog')
            ->assertSee('stage-canvas')
            ->assertSee('add-source-dialog')
            ->assertSee('rename-scene-dialog')
            ->assertSee('Host Camera')
            ->assertDontSee('@vite/client', false);
    }

    public function test_environment_favicons_switch_between_white_local_and_gold_production_symbols(): void
    {
        $this->assertFileExists(public_path('favicon-local.svg'));
        $this->assertFileExists(public_path('favicon-prod.svg'));

        $this->assertStringContainsString('#FAF7EE', (string) file_get_contents(public_path('favicon-local.svg')));
        $this->assertStringContainsString('#F4E185', (string) file_get_contents(public_path('favicon-prod.svg')));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('favicon-local.svg', false);

        $this->app->detectEnvironment(static fn (): string => 'production');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('favicon-prod.svg', false);

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('favicon-prod.svg', false);
    }

    public function test_studio_control_center_dispatches_scene_source_transform_and_inspect_commands(): void
    {
        Event::fake([ObsCommandDispatched::class, ObsScreenUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->create(['name' => 'Control One']);
        $screen = ObsScreen::factory()->create(['obs_studio_id' => $studio->id]);
        $macro = ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);

        $this->actingAs($user);

        foreach ([
            ['SetSceneName', ['sceneName' => 'Old', 'newSceneName' => 'Main']],
            ['RemoveScene', ['sceneName' => 'Temp']],
            ['CreateInput', ['sceneName' => 'Main', 'inputName' => 'Overlay', 'inputKind' => 'browser_source', 'inputSettings' => ['url' => 'https://ob/test']]],
            ['SetSceneItemTransform', ['sceneName' => 'Main', 'sceneItemId' => 2, 'sceneItemTransform' => ['positionX' => 120, 'positionY' => 80]]],
            ['SetSceneItemIndex', ['sceneName' => 'Main', 'sceneItemId' => 2, 'sceneItemIndex' => 1]],
            ['SetSceneItemLocked', ['sceneName' => 'Main', 'sceneItemId' => 2, 'sceneItemLocked' => true]],
            ['RemoveSceneItem', ['sceneName' => 'Main', 'sceneItemId' => 2]],
        ] as [$reqType, $reqData]) {
            $this->postJson(route('studio.command', ['studio' => $studio->slug]), [
                'request_type' => $reqType,
                'request_data' => $reqData,
            ])
                ->assertOk()
                ->assertJsonPath('ok', true)
                ->assertJsonPath('request_type', $reqType);
        }

        Event::assertDispatched(ObsCommandDispatched::class, 7);

        $inspectRes = $this->postJson(route('studio.command', ['studio' => $studio->slug]), [
            'request_type' => 'obsisstant.FetchInputSettings',
            'request_data' => ['inputName' => 'Overlay'],
        ])->assertOk();

        $uuid = $inspectRes->json('command_uuid');
        $log = ObsCommandLog::query()->where('command_uuid', $uuid)->firstOrFail();
        $log->update([
            'status' => 'succeeded',
            'response_data' => ['inputSettings' => ['url' => 'https://ob/test']],
        ]);

        $this->getJson(route('studio.command-result', ['studio' => $studio->slug, 'commandUuid' => $uuid]))
            ->assertOk()
            ->assertJsonPath('status', 'succeeded')
            ->assertJsonPath('response_data.inputSettings.url', 'https://ob/test');

        $this->postJson(route('studio.macro', ['studio' => $studio->slug, 'macro' => $macro->id]))
            ->assertOk();

        $this->postJson(route('studio.screen-live', ['studio' => $studio->slug, 'screen' => $screen->id]), [
            'state' => ['guest_name' => 'Director Mode'],
        ])->assertOk()->assertJsonPath('state.guest_name', 'Director Mode');

        $this->postJson(route('studio.screen-push', ['studio' => $studio->slug, 'screen' => $screen->id]), [
            'scene_name' => 'Main',
        ])->assertOk();
    }

    public function test_obs_agent_preview_frame_upload_and_state_replacement_work_correctly(): void
    {
        Event::fake([ObsPreviewFrameUpdated::class, ObsStudioStateUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->create([
            'agent_token' => 'studio-token-123456',
            'live_state' => [
                'sceneItemsByScene' => [
                    'Old Scene' => [['sceneItemId' => 1, 'sourceName' => 'Stale Source']],
                ],
            ],
        ]);

        // Test live preview stream toggle command
        $this->actingAs($user)
            ->postJson(route('studio.preview-toggle', ['studio' => $studio->slug]), [
                'active' => true,
                'scene_name' => 'Main Scene',
            ])
            ->assertOk()
            ->assertJsonPath('active', true);

        // No frame cached yet -> 204
        $this->actingAs($user)
            ->get(route('studio.preview-frame', ['studio' => $studio->slug]))
            ->assertNoContent();

        // Simulate obs-agent uploading 1x1 JPEG pixel preview frame
        $tinyJpeg = 'data:image/jpeg;base64,'.base64_encode("\xFF\xD8\xFF\xE0tinyjpeg");
        $this->withHeader('Authorization', 'Bearer studio-token-123456')
            ->postJson(route('api.obs-agent.preview-frame'), [
                'scene_name' => 'Main Scene',
                'image_data' => $tinyJpeg,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        Event::assertDispatched(ObsPreviewFrameUpdated::class);

        $this->actingAs($user)
            ->get(route('studio.preview-frame', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->postJson(route('studio.command', ['studio' => $studio->slug]), [
            'request_type' => 'SetCurrentSceneCollection',
            'request_data' => ['sceneCollectionName' => 'Podcast Layout'],
        ])->assertOk()->assertJsonPath('request_type', 'SetCurrentSceneCollection');

        $this->postJson(route('studio.command', ['studio' => $studio->slug]), [
            'request_type' => 'CreateSceneCollection',
            'request_data' => ['sceneCollectionName' => 'Event Finals'],
        ])->assertOk()->assertJsonPath('request_type', 'CreateSceneCollection');

        $this->withHeader('Authorization', 'Bearer studio-token-123456')
            ->postJson(route('api.obs-agent.state'), [
                'live_state' => [
                    'currentSceneCollection' => 'Podcast Layout',
                    'sceneCollections' => ['Default', 'Podcast Layout'],
                    'scenes' => [['sceneName' => 'Main Scene']],
                    'sceneItemsByScene' => [
                        'Main Scene' => [['sceneItemId' => 5, 'sourceName' => 'Camera']],
                    ],
                ],
            ])
            ->assertOk();

        $studio->refresh();
        $this->assertArrayNotHasKey('Old Scene', $studio->getSceneItemsByScene());
        $this->assertSame('Camera', $studio->getSceneItems('Main Scene')[0]['sourceName']);
        $this->assertSame('Podcast Layout', $studio->getCurrentSceneCollection());
        $this->assertSame(['Default', 'Podcast Layout'], $studio->getSceneCollections());
    }
}
