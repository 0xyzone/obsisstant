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
            ->assertSee('deck-tiles-grid', false)
            ->assertSee('deck-edit-mode-btn', false)
            ->assertSee('tile-editor-dialog', false)
            ->assertSee('timer-controls-dialog', false)
            ->assertSee('deck-open-timer-btn', false)
            ->assertSee('tm-custom-min', false)
            ->assertSee('deck-uni-l3-show', false);
    }

    public function test_mobile_deck_configurable_tile_layout_persists_broadcasts_and_resets(): void
    {
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create([
            'control_mode' => 'obs',
        ]);
        $collection = ScreenCollection::factory()->for($user)->create([
            'obs_studio_id' => $studio->id,
            'name' => 'Grand Finals Deck',
        ]);
        $viewA = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Opening Slate', 'sort_order' => 1]);
        $viewB = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Live Cam', 'sort_order' => 2]);
        $collection->update(['active_view_id' => $viewA->id]);

        // Auto-generates view switch tiles by default
        $defaultLayout = $collection->fresh(['views'])->getNormalizedDeckLayout();
        $this->assertSame(0, $defaultLayout['columns']);
        $viewTileIds = array_values(array_map(
            static fn (array $t): int => (int) ($t['config']['view_id'] ?? 0),
            array_filter($defaultLayout['tiles'], static fn (array $t): bool => $t['type'] === 'switch_view')
        ));
        $this->assertSame([$viewA->id, $viewB->id], $viewTileIds);

        // Unauthorized user cannot save deck layout
        $this->actingAs($stranger)
            ->putJson(route('studio.collections.deck-layout', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'columns' => 3,
                'tiles' => [],
            ])
            ->assertForbidden();

        // Owner saves custom tile layout with app view switchers + custom lower third preset
        $saveRes = $this->actingAs($user)
            ->putJson(route('studio.collections.deck-layout', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'columns' => 3,
                'tiles' => [
                    [
                        'id' => 'tile_custom_a',
                        'type' => 'switch_view',
                        'label' => 'Go Opening',
                        'subtitle' => 'Fade 450ms',
                        'color' => 'gold',
                        'span' => 2,
                        'config' => ['view_id' => $viewA->id, 'transition_type' => 'fade'],
                    ],
                    [
                        'id' => 'tile_speaker_1',
                        'type' => 'lower_third',
                        'label' => 'Host: Alex',
                        'subtitle' => 'Caster Desk',
                        'color' => 'emerald',
                        'span' => 1,
                        'config' => ['action' => 'show', 'title' => 'Alex', 'subtitle' => 'Main Host'],
                    ],
                ],
            ])
            ->assertOk();

        $saveRes->assertJsonPath('collection.deck_layout.columns', 3);
        $saveRes->assertJsonPath('collection.deck_layout.tiles.0.label', 'Go Opening');
        $saveRes->assertJsonPath('collection.deck_layout.tiles.0.span', 2);
        $saveRes->assertJsonPath('collection.deck_layout.tiles.1.type', 'lower_third');

        // Mobile Deck defaults to Stage Tiles mode when studio owns collections
        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('Go Opening')
            ->assertSee('Host: Alex')
            ->assertSee('Stage Tiles');

        // Reset restores auto-generated defaults from collection views
        $resetRes = $this->actingAs($user)
            ->putJson(route('studio.collections.deck-layout', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'reset' => true,
            ])
            ->assertOk();

        $resetRes->assertJsonPath('collection.deck_layout.columns', 0);
        $resetRes->assertJsonPath('collection.deck_layout.tiles.0.label', 'Opening Slate');

        Event::assertDispatched(ScreenCollectionUpdated::class, 2);
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

    public function test_mobile_deck_and_switch_view_support_transition_duration_up_to_3000ms_max(): void
    {
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create([
            'obs_studio_id' => $studio->id,
            'default_transition' => ['type' => 'fade', 'duration_ms' => 4500],
        ]);
        $viewA = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Intro', 'sort_order' => 1]);
        $viewB = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'name' => 'Gameplay', 'sort_order' => 2]);
        $collection->update(['active_view_id' => $viewA->id]);

        // Mobile Deck renders transition duration control capped at 3000ms
        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('id="deck-uni-tx-duration"', false)
            ->assertSee('max="3000"', false)
            ->assertSee('MAX_TX_DURATION_MS = 3000', false)
            ->assertSee('cfg-tx-duration', false);

        // Switching views accepts up to 3000ms
        $this->actingAs($user)
            ->postJson(route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'view_id' => $viewB->id,
                'transition' => [
                    'type' => 'slide_left',
                    'duration_ms' => 3000,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('collection.runtime_state.last_transition.type', 'slide_left')
            ->assertJsonPath('collection.runtime_state.last_transition.duration_ms', 3000);

        // Switching views rejects durations above 3000ms
        $this->actingAs($user)
            ->postJson(route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'view_id' => $viewA->id,
                'transition' => [
                    'type' => 'fade',
                    'duration_ms' => 3001,
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['transition.duration_ms']);

        // Inherited collection default duration > 3000ms is clamped to 3000ms by ScreenCollectionService
        $this->actingAs($user)
            ->postJson(route('studio.collections.switch', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'view_id' => $viewA->id,
                'transition' => null,
            ])
            ->assertOk()
            ->assertJsonPath('collection.runtime_state.last_transition.duration_ms', 3000);

        // Configurable tile deck layout saves per-tile transition_duration_ms <= 3000 and rejects > 3000
        $this->actingAs($user)
            ->putJson(route('studio.collections.deck-layout', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'columns' => 3,
                'tiles' => [
                    [
                        'id' => 'tile_slow_fade',
                        'type' => 'switch_view',
                        'label' => 'Slow Fade',
                        'subtitle' => '',
                        'color' => 'gold',
                        'span' => 1,
                        'config' => [
                            'view_id' => $viewB->id,
                            'transition_type' => 'fade',
                            'transition_duration_ms' => 1850,
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('collection.deck_layout.tiles.0.config.view_id', $viewB->id)
            ->assertJsonPath('collection.deck_layout.tiles.0.config.transition_type', 'fade')
            ->assertJsonPath('collection.deck_layout.tiles.0.config.transition_duration_ms', 1850);

        $this->actingAs($user)
            ->putJson(route('studio.collections.deck-layout', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'columns' => 3,
                'tiles' => [
                    [
                        'id' => 'tile_too_slow',
                        'type' => 'switch_view',
                        'label' => 'Too Slow',
                        'subtitle' => '',
                        'color' => 'gold',
                        'span' => 1,
                        'config' => [
                            'view_id' => $viewB->id,
                            'transition_type' => 'fade',
                            'transition_duration_ms' => 3500,
                        ],
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tiles.0.config.transition_duration_ms']);
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

    public function test_timer_countdown_provides_clock_skew_immune_millisecond_anchors_and_accurate_controls(): void
    {
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id]);
        $view = ScreenView::factory()->create(['screen_collection_id' => $collection->id]);
        $collection->update(['active_view_id' => $view->id]);

        $this->travelTo(now()->startOfSecond());

        try {
            // Set 5:00 (300s) then Start -> remaining_ms MUST be exact 300000ms (05:00), never skewed!
            $this->actingAs($user)
                ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                    'timer' => ['action' => 'set', 'duration_seconds' => 300],
                ])
                ->assertOk()
                ->assertJsonPath('collection.runtime_state.timer.remaining_seconds', 300)
                ->assertJsonPath('collection.runtime_state.timer.remaining_ms', 300000);

            $startRes = $this->actingAs($user)
                ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                    'timer' => ['action' => 'start'],
                ])
                ->assertOk();

            $startRes->assertJsonPath('collection.runtime_state.timer.status', 'running');
            $startRes->assertJsonPath('collection.runtime_state.timer.remaining_seconds', 300);
            $this->assertGreaterThan(0, (int) $startRes->json('collection.server_now_ms'));
            $this->assertSame(300000, (int) $startRes->json('collection.runtime_state.timer.remaining_ms'));

            // Advance server time by exactly 12 seconds -> state endpoint reports exact 288s / 288000ms
            $this->travel(12)->seconds();

            // Calling Start again while already running is idempotent (must NOT reset back to 300s!)
            $idempotentRes = $this->actingAs($user)
                ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                    'timer' => ['action' => 'start'],
                ])
                ->assertOk();
            $idempotentRes->assertJsonPath('collection.runtime_state.timer.remaining_seconds', 288);

            // Pause preserves exact remaining 288s
            $pauseRes = $this->actingAs($user)
                ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                    'timer' => ['action' => 'pause'],
                ])
                ->assertOk();
            $pauseRes->assertJsonPath('collection.runtime_state.timer.status', 'paused');
            $pauseRes->assertJsonPath('collection.runtime_state.timer.remaining_seconds', 288);
            $pauseRes->assertJsonPath('collection.runtime_state.timer.remaining_ms', 288000);

            // Add +60s while paused -> 348s
            $this->actingAs($user)
                ->postJson(route('studio.collections.runtime', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                    'timer' => ['action' => 'add_seconds', 'seconds' => 60],
                ])
                ->assertOk()
                ->assertJsonPath('collection.runtime_state.timer.remaining_seconds', 348)
                ->assertJsonPath('collection.runtime_state.timer.remaining_ms', 348000);
        } finally {
            $this->travelBack();
        }
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

    public function test_collection_theme_supports_transparent_and_animated_video_default_bg_without_view_override_clobber(): void
    {
        Storage::fake('public');
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id]);
        $view = ScreenView::factory()->create([
            'screen_collection_id' => $collection->id,
            'name' => 'Start',
            'background_mode' => 'theme',
        ]);
        $collection->update(['active_view_id' => $view->id]);

        // Saving a view with blank theme_override fields persists null instead of { accent_color: null }
        $this->actingAs($user)
            ->putJson(route('studio.collections.views.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $view->id,
            ]), [
                'name' => 'Start',
                'background_mode' => 'theme',
                'theme_override' => [
                    'accent_color' => null,
                    'surface_color' => '',
                ],
                'layers' => [],
            ])
            ->assertOk();

        $this->assertNull($view->fresh()->theme_override);

        // Setting Collection Default Theme to Transparent + Custom Accent / Text color
        $transparentRes = $this->actingAs($user)
            ->putJson(route('studio.collections.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
            ]), [
                'theme' => [
                    'accent_color' => '#E05297',
                    'secondary_color' => '#E05297',
                    'surface_color' => '#141118',
                    'text_color' => '#FFF5FA',
                    'background_mode' => 'transparent',
                    'background_asset_id' => null,
                ],
            ])
            ->assertOk();

        $transparentRes->assertJsonPath('collection.theme.accent_color', '#E05297');
        $transparentRes->assertJsonPath('collection.theme.text_color', '#FFF5FA');
        $transparentRes->assertJsonPath('collection.theme.background_mode', 'transparent');
        $transparentRes->assertJsonPath('collection.theme.background_asset_url', null);

        // Uploading an animated MP4 background video and assigning it as the Collection Default Background
        $videoUpload = $this->actingAs($user)
            ->post(route('studio.collections.assets.store', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
            ]), [
                'file' => UploadedFile::fake()->create('neon-loop.mp4', 256, 'video/mp4'),
                'type' => 'video',
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $videoAssetId = (int) $videoUpload->json('asset.id');
        $this->assertSame('video', $videoUpload->json('asset.type'));

        $videoBgRes = $this->actingAs($user)
            ->putJson(route('studio.collections.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
            ]), [
                'theme' => [
                    'background_mode' => 'asset',
                    'background_asset_id' => $videoAssetId,
                ],
            ])
            ->assertOk();

        $videoBgRes->assertJsonPath('collection.theme.background_mode', 'asset');
        $videoBgRes->assertJsonPath('collection.theme.background_asset_id', $videoAssetId);
        $videoBgRes->assertJsonPath('collection.theme.background_asset_type', 'video');
        $this->assertNotEmpty($videoBgRes->json('collection.theme.background_asset_url'));

        // Stranger's asset cannot be attached as default background
        $foreignAsset = ScreenAsset::factory()->create(['user_id' => $stranger->id, 'type' => 'video']);
        $this->actingAs($user)
            ->putJson(route('studio.collections.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
            ]), [
                'theme' => [
                    'background_mode' => 'asset',
                    'background_asset_id' => $foreignAsset->id,
                ],
            ])
            ->assertNotFound();

        // Canvas Builder and Stage Player render the new live theme + default BG controls and helpers
        $this->actingAs($user)
            ->get(route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk()
            ->assertSee('id="col-bg-mode"', false)
            ->assertSee('id="col-bg-asset"', false)
            ->assertSee('id="col-bg-upload-input"', false)
            ->assertSee('id="vp-bg-upload-input"', false)
            ->assertSee('applyLiveThemeFromInputs', false)
            ->assertSee('resolveEffectiveBackground', false);

        $this->get(route('obs.collection.show', ['token' => $collection->public_token]))
            ->assertOk()
            ->assertSee('resolveEffectiveBackground', false)
            ->assertSee('applyRootThemeVars', false);
    }
}
