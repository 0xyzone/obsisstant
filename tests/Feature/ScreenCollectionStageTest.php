<?php

namespace Tests\Feature;

use App\Events\ObsCommandDispatched;
use App\Events\ScreenCollectionUpdated;
use App\Filament\Resources\ScreenCollections\Pages\CreateScreenCollection;
use App\Filament\Resources\ScreenCollections\Pages\ListScreenCollections;
use App\Models\ObsStudio;
use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ScreenCollectionStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_control_mode_switch_seeds_default_collection_and_isolates_users(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create(['name' => 'Tournament Arena']);

        $this->actingAs($stranger)
            ->postJson(route('studio.control-mode', ['studio' => $studio->slug]), ['control_mode' => 'screens'])
            ->assertForbidden();

        $response = $this->actingAs($user)
            ->postJson(route('studio.control-mode', ['studio' => $studio->slug]), [
                'control_mode' => 'screens',
            ])
            ->assertOk()
            ->assertJsonPath('control_mode', 'screens');

        $collectionId = $response->json('active_screen_collection_id');
        $this->assertNotNull($collectionId);

        $collection = ScreenCollection::query()->with('views')->findOrFail($collectionId);
        $this->assertSame('screens', $studio->fresh()->control_mode);
        $this->assertCount(5, $collection->views);

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('Unified Screen')
            ->assertSee('Screen Collections')
            ->assertSee('Starting Soon')
            ->assertSee('data-uni-console-tab="timer"', false)
            ->assertSee('data-uni-console-tab="scoreboard"', false)
            ->assertSee('data-uni-console-tab="graphics"', false)
            ->assertSee('audio-mixer-filter', false);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('Unified Screen Stage')
            ->assertSee('Scoreboard')
            ->assertSee('deck-uni-l3-show', false);
    }

    public function test_switching_views_updates_active_view_transitions_and_broadcasts(): void
    {
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id]);
        $viewA = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Intro', 'sort_order' => 1]);
        $viewB = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Match Cam', 'sort_order' => 2]);
        $collection->update(['active_view_id' => $viewA->id]);

        Storage::fake('public');
        $stingerPath = UploadedFile::fake()->create('stinger.webm', 64, 'video/webm')->store('screen-assets', 'public');
        $stingerAsset = ScreenAsset::factory()->create([
            'user_id' => $user->id,
            'screen_collection_id' => $collection->id,
            'type' => 'stinger',
            'path' => $stingerPath,
            'mime_type' => 'video/webm',
            'original_filename' => 'stinger.webm',
        ]);

        $res = $this->actingAs($user)
            ->postJson(route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'view_id' => $viewB->id,
                'transition' => [
                    'type' => 'stinger',
                    'duration_ms' => 700,
                    'swap_point_ms' => 350,
                    'stinger_asset_id' => $stingerAsset->id,
                ],
            ])
            ->assertOk();

        $res->assertJsonPath('collection.active_view_id', $viewB->id);
        $res->assertJsonPath('collection.runtime_state.last_transition.seq', 1);
        $res->assertJsonPath('collection.runtime_state.last_transition.type', 'stinger');
        $res->assertJsonPath('collection.runtime_state.last_transition.duration_ms', 700);
        $res->assertJsonPath('collection.runtime_state.last_transition.swap_point_ms', 350);

        Event::assertDispatched(ScreenCollectionUpdated::class, static function (ScreenCollectionUpdated $event) use ($collection, $viewB): bool {
            return $event->collection->id === $collection->id && (int) $event->collection->active_view_id === $viewB->id;
        });
    }

    public function test_runtime_timer_scoreboard_and_layer_toggles_preserve_transition_sequence(): void
    {
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id]);
        $view = ScreenView::factory()->create(['screen_collection_id' => $collection->id]);
        $collection->update(['active_view_id' => $view->id]);

        $this->actingAs($user)
            ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'timer' => ['action' => 'set', 'duration_seconds' => 180, 'auto_start' => true],
                'scoreboard' => ['team_a_name' => 'Falcons', 'team_b_name' => 'Vikings', 'delta_a' => 2],
                'lower_third' => ['visible' => true, 'title' => 'Finals Host', 'subtitle' => 'Main Stage'],
                'alert' => ['visible' => true, 'message' => 'Match Starting Now'],
                'toggle_layer' => ['layer_id' => 'lyr_demo', 'visible' => false],
            ])
            ->assertOk()
            ->assertJsonPath('collection.runtime_state.timer.status', 'running')
            ->assertJsonPath('collection.runtime_state.timer.duration_seconds', 180)
            ->assertJsonPath('collection.runtime_state.scoreboard.team_a_name', 'Falcons')
            ->assertJsonPath('collection.runtime_state.scoreboard.team_a_score', 2)
            ->assertJsonPath('collection.runtime_state.lower_third.title', 'Finals Host')
            ->assertJsonPath('collection.runtime_state.alert.visible', true)
            ->assertJsonPath('collection.runtime_state.hidden_layers.0', 'lyr_demo')
            ->assertJsonPath('collection.runtime_state.last_transition.seq', 0);

        $this->actingAs($user)
            ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'scoreboard' => ['swap' => true],
                'timer' => ['action' => 'pause'],
            ])
            ->assertOk()
            ->assertJsonPath('collection.runtime_state.scoreboard.team_a_name', 'Vikings')
            ->assertJsonPath('collection.runtime_state.scoreboard.team_b_score', 2)
            ->assertJsonPath('collection.runtime_state.timer.status', 'paused');

        Event::assertDispatched(ScreenCollectionUpdated::class, 2);
    }

    public function test_public_stage_player_renders_and_handles_idempotent_timer_end_logic(): void
    {
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $collection = ScreenCollection::factory()->for($user)->create(['name' => 'Spring Major']);
        $viewTarget = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Main Stage', 'sort_order' => 2]);
        $viewStart = ScreenView::factory()->create([
            'screen_collection_id' => $collection->id,
            'name' => 'Starting Soon',
            'sort_order' => 1,
            'logic_rules' => [
                'on_timer_end_action' => 'switch_view',
                'on_timer_end_target_view_id' => $viewTarget->id,
            ],
        ]);
        $collection->update(['active_view_id' => $viewStart->id]);

        $this->get(route('obs.collection.show', ['token' => $collection->public_token]))
            ->assertOk()
            ->assertSee('Spring Major')
            ->assertSee("obs.collection.{$collection->public_token}")
            ->assertSee('cutout-mask');

        $marker = '2026-11-24T12:00:00Z';
        $this->postJson(route('obs.collection.logic-trigger', ['token' => $collection->public_token]), [
            'event' => 'timer_end',
            'view_id' => $viewStart->id,
            'marker' => $marker,
        ])
            ->assertOk()
            ->assertJsonPath('handled', true)
            ->assertJsonPath('target_view_id', $viewTarget->id);

        $this->assertSame($viewTarget->id, $collection->fresh()->active_view_id);

        // Duplicate notification with same marker from a second browser instance is debounced
        $this->postJson(route('obs.collection.logic-trigger', ['token' => $collection->public_token]), [
            'event' => 'timer_end',
            'view_id' => $viewStart->id,
            'marker' => $marker,
        ])
            ->assertOk()
            ->assertJsonPath('handled', false);
    }

    public function test_canvas_builder_view_crud_asset_upload_and_obs_stage_push_work(): void
    {
        Storage::fake('public');
        Event::fake([ObsCommandDispatched::class, ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create([
            'obs_studio_id' => $studio->id,
            'obs_scene_name' => 'Tournament Single Scene',
            'obs_source_name' => 'Tournament Browser Stage',
        ]);
        $view = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Desk']);
        $collection->update(['active_view_id' => $view->id]);

        $this->actingAs($user)
            ->get(route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk()
            ->assertSee('Canvas Editor')
            ->assertSee('Collection Assets')
            ->assertSee('data-quick-add="camera_cutout"', false)
            ->assertSee('data-left-tab="theme"', false);

        // Upload PNG asset and verify public binary route serves it cleanly
        $uploadRes = $this->actingAs($user)
            ->postJson(route('studio.collections.assets.store', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'file' => UploadedFile::fake()->image('tournament-logo.png', 240, 240),
                'type' => 'image',
            ])
            ->assertCreated();

        $assetUrl = $uploadRes->json('asset.url');
        $assetId = $uploadRes->json('asset.id');
        $this->assertNotEmpty($assetUrl);
        $this->get($assetUrl)->assertOk();

        // Duplicate & update view with canvas layers
        $dupRes = $this->actingAs($user)
            ->postJson(route('studio.collections.views.duplicate', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $view->id,
            ]))
            ->assertCreated();
        $dupViewId = $dupRes->json('view_id');

        $this->actingAs($user)
            ->putJson(route('studio.collections.views.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $dupViewId,
            ]), [
                'name' => 'Desk Updated',
                'background_mode' => 'transparent',
                'layers' => [
                    ['id' => 'lyr_a', 'type' => 'camera_cutout', 'name' => 'Cam Hole', 'x' => 100, 'y' => 100, 'w' => 640, 'h' => 360],
                ],
            ])
            ->assertOk();

        // Push Single Scene to OBS with rerouteAudio enabled
        $this->actingAs($user)
            ->postJson(route('studio.collections.push', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk()
            ->assertJsonPath('stage_url', $collection->getStageUrl(true));

        Event::assertDispatched(ObsCommandDispatched::class, static function (ObsCommandDispatched $event) use ($collection): bool {
            $log = $event->commandLog;

            return $log->request_type === 'obsisstant.PushBrowserScreen'
                && ($log->request_data['sceneName'] ?? '') === 'Tournament Single Scene'
                && ($log->request_data['inputName'] ?? '') === 'Tournament Browser Stage'
                && ($log->request_data['url'] ?? '') === $collection->getStageUrl(true)
                && ($log->request_data['rerouteAudio'] ?? false) === true;
        });

        // Deleting one view works; deleting final remaining view is rejected (422)
        $this->actingAs($user)
            ->deleteJson(route('studio.collections.views.destroy', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $dupViewId,
            ]))
            ->assertOk();

        $this->actingAs($user)
            ->deleteJson(route('studio.collections.views.destroy', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $view->id,
            ]))
            ->assertStatus(422);

        $this->actingAs($user)
            ->deleteJson(route('studio.collections.assets.destroy', ['studio' => $studio->slug, 'asset' => $assetId]))
            ->assertOk();
    }

    public function test_filament_screen_collection_resource_lists_and_creates_with_starter_views(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $mine = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id, 'name' => 'My Expo']);
        $foreign = ScreenCollection::factory()->for($stranger)->create(['name' => 'Stranger Expo']);

        $this->actingAs($user);

        Livewire::test(ListScreenCollections::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$foreign]);

        Livewire::test(CreateScreenCollection::class)
            ->fillForm([
                'name' => 'Summer League',
                'obs_studio_id' => $studio->id,
                'width' => 1920,
                'height' => 1080,
                'obs_scene_name' => 'Summer Stage',
                'obs_source_name' => 'Summer Browser',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = ScreenCollection::query()->where('name', 'Summer League')->firstOrFail();
        $this->assertCount(5, $created->views);
    }
}
