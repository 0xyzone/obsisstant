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
use Illuminate\Support\Facades\Broadcast;
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

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create([
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
            ->assertSee('Scene Sources')
            ->assertSee('Audio Mixer')
            ->assertSee('Live Overlay Push')
            ->assertSee('no-source-selected-card')
            ->assertSee('selected-source-card')
            ->assertSee('add-source-dialog')
            ->assertSee('add-source-dynamic-fields')
            ->assertSee('rename-scene-dialog')
            ->assertSee('delete-source-dialog')
            ->assertSee('visual-settings-fields')
            ->assertSee('rename-source-input')
            ->assertSee('Width (px)')
            ->assertSee('Height (px)')
            ->assertSee('Delete Source')
            ->assertSee('INPUT_KIND_CATALOG')
            ->assertSee('listenForWhisper', false)
            ->assertDontSee('stage-canvas', false)
            ->assertDontSee('live-view-mode', false)
            ->assertDontSee('obs.preview.subscribe', false)
            ->assertSee('Host Camera')
            ->assertDontSee('@vite/client', false);
    }

    public function test_studio_snapshot_and_empty_object_json_serialization_work_correctly(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create([
            'name' => 'Snapshot Studio',
            'agent_token' => 'snapshot-token-999',
            'live_state' => [
                'currentProgramScene' => 'Bg',
                'scenes' => [['sceneName' => 'Bg']],
            ],
        ]);

        $this->get(route('studio.snapshot', ['studio' => $studio->slug]))->assertRedirect();

        $log = ObsCommandLog::create([
            'obs_studio_id' => $studio->id,
            'command_uuid' => '00000000-0000-0000-0000-000000000123',
            'request_type' => 'CreateInput',
            'request_data' => [
                'sceneName' => 'Bg',
                'inputName' => 'Clean Source',
                'inputKind' => 'browser_source',
                'inputSettings' => [],
                'sceneItemEnabled' => true,
            ],
            'status' => 'dispatched',
        ]);

        $broadcastJson = json_encode((new ObsCommandDispatched($log))->broadcastWith());
        $this->assertStringContainsString('"inputSettings":{}', (string) $broadcastJson);
        $this->assertStringNotContainsString('"inputSettings":[]', (string) $broadcastJson);

        $bootstrapRes = $this->withHeader('Authorization', 'Bearer snapshot-token-999')
            ->get(route('api.obs-agent.bootstrap'))
            ->assertOk();

        $this->assertStringContainsString('"inputSettings":{}', (string) $bootstrapRes->getContent());
        $this->assertStringNotContainsString('"inputSettings":[]', (string) $bootstrapRes->getContent());

        $this->actingAs($user)
            ->getJson(route('studio.snapshot', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertJsonPath('status', 'online')
            ->assertJsonPath('is_online', true)
            ->assertJsonPath('live_state.currentProgramScene', 'Bg')
            ->assertJsonPath('recent_logs.0.request_type', 'CreateInput');
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
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Control One']);
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
        $studio = ObsStudio::factory()->for($user)->create([
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

        // Preview stream toggle must stay ephemeral and not pollute obs_command_logs
        $this->assertDatabaseMissing('obs_command_logs', [
            'obs_studio_id' => $studio->id,
            'request_type' => 'obsisstant.SetPreviewStream',
        ]);

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

        Event::assertDispatched(ObsPreviewFrameUpdated::class, static fn (ObsPreviewFrameUpdated $event): bool => $event->imageData === $tinyJpeg);

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

    public function test_users_are_strictly_isolated_across_studios_actions_and_reverb_channels(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $studio = ObsStudio::factory()->online()->for($owner)->create(['name' => 'Private Studio']);
        $screen = ObsScreen::factory()->create(['obs_studio_id' => $studio->id]);
        $macro = ObsMacro::factory()->create(['obs_studio_id' => $studio->id]);

        $this->actingAs($intruder)
            ->get(route('studio.show', ['studio' => $studio->slug]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->getJson(route('studio.snapshot', ['studio' => $studio->slug]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson(route('studio.command', ['studio' => $studio->slug]), [
                'request_type' => 'StartStream',
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson(route('studio.macro', ['studio' => $studio->slug, 'macro' => $macro->id]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson(route('studio.screen-live', ['studio' => $studio->slug, 'screen' => $screen->id]), [
                'state' => ['guest_name' => 'Hacked'],
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson(route('studio.preview-toggle', ['studio' => $studio->slug]), [
                'active' => true,
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->get(route('studio.preview-frame', ['studio' => $studio->slug]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson(route('obs.deck.command', ['studio' => $studio->slug]), [
                'request_type' => 'ToggleStream',
            ])
            ->assertForbidden();

        // Owner's /studio index ignores foreign studios
        $this->actingAs($intruder)
            ->get(route('studio.index'))
            ->assertRedirect('/admin/obs-studios');

        // Channel authorizer denies intruder on owner's studio and personal relay channels
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'testkey',
            'broadcasting.connections.reverb.secret' => 'testsecret',
            'broadcasting.connections.reverb.app_id' => '12345',
        ]);
        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        $this->actingAs($intruder)
            ->post('/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$studio->id}",
            ])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post('/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.user.{$owner->id}.relay",
            ])
            ->assertForbidden();

        $this->actingAs($owner)
            ->post('/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.studio.{$studio->id}",
            ])
            ->assertOk();

        $this->actingAs($owner)
            ->post('/broadcasting/auth', [
                'socket_id' => '12345.67890',
                'channel_name' => "private-obs.user.{$owner->id}.relay",
            ])
            ->assertOk();
    }

    public function test_scene_item_transform_state_sync_persists_without_reverting_on_snapshot(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create([
            'agent_token' => 'transform-sync-token',
            'live_state' => [
                'currentProgramScene' => 'New Scene',
                'scenes' => [['sceneName' => 'New Scene', 'sceneIndex' => 0]],
                'sceneItemsByScene' => [
                    'New Scene' => [
                        [
                            'sceneItemId' => 1,
                            'sourceName' => 'Just a test',
                            'inputKind' => 'text_ft2_source_v2',
                            'sceneItemEnabled' => true,
                            'sceneItemLocked' => false,
                            'sceneItemTransform' => [
                                'positionX' => 0,
                                'positionY' => 0,
                                'width' => 512,
                                'height' => 244,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $commandRes = $this->actingAs($user)
            ->postJson(route('studio.command', ['studio' => $studio->slug]), [
                'request_type' => 'SetSceneItemTransform',
                'request_data' => [
                    'sceneName' => 'New Scene',
                    'sceneItemId' => 1,
                    'sceneItemTransform' => [
                        'positionX' => 564,
                        'positionY' => 337,
                    ],
                ],
            ])
            ->assertOk();

        $commandUuid = $commandRes->json('command_uuid');

        // Push authoritative transform state before ack, matching obs-agent syncIncrementalCommandState
        $this->withHeader('Authorization', 'Bearer transform-sync-token')
            ->postJson(route('api.obs-agent.state'), [
                'live_state' => [
                    'sceneItemsByScene' => [
                        'New Scene' => [
                            [
                                'sceneItemId' => 1,
                                'sourceName' => 'Just a test',
                                'inputKind' => 'text_ft2_source_v2',
                                'sceneItemEnabled' => true,
                                'sceneItemLocked' => false,
                                'sceneItemTransform' => [
                                    'positionX' => 564,
                                    'positionY' => 337,
                                    'width' => 512,
                                    'height' => 244,
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer transform-sync-token')
            ->postJson("/api/obs-agent/commands/{$commandUuid}/ack", [
                'studio_id' => $studio->id,
                'status' => 'succeeded',
                'response_data' => [],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->getJson(route('studio.snapshot', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertJsonPath('live_state.sceneItemsByScene.New Scene.0.sceneItemTransform.positionX', 564)
            ->assertJsonPath('live_state.sceneItemsByScene.New Scene.0.sceneItemTransform.positionY', 337);

        $agentJs = file_get_contents(base_path('obs-agent/src/index.js')) ?: '';
        $this->assertStringContainsString('EventSubscription.All | EventSubscription.SceneItemTransformChanged', $agentJs);
        $this->assertStringContainsString('syncIncrementalCommandState', $agentJs);

        $bladeJs = file_get_contents(resource_path('views/studio/control-center.blade.php')) ?: '';
        $this->assertStringContainsString('commitSceneItemTransform', $bladeJs);
        $this->assertStringContainsString('applyIncomingLiveState', $bladeJs);
    }
}
