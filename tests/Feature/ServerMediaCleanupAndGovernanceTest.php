<?php

namespace Tests\Feature;

use App\Events\ScreenCollectionUpdated;
use App\Filament\Pages\KnowledgeBase;
use App\Filament\Pages\ObsControlDeck;
use App\Filament\Pages\StudioSettings;
use App\Filament\Resources\ObsMacros\ObsMacroResource;
use App\Filament\Resources\ObsScreens\ObsScreenResource;
use App\Filament\Resources\ObsStudios\ObsStudioResource;
use App\Filament\Resources\ScreenCollections\ScreenCollectionResource;
use App\Models\ObsStudio;
use App\Models\ScreenAsset;
use App\Models\ScreenCollection;
use App\Models\ScreenView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ServerMediaCleanupAndGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_obs_agent_uses_batched_queries_low_volume_events_keepalive_and_single_studio_lease(): void
    {
        $agentSource = File::get(base_path('obs-agent/src/index.js'));

        // Must NOT subscribe to EventSubscription.All or High-Volume SceneItemTransformChanged
        $this->assertStringNotContainsString('EventSubscription.All', $agentSource);
        $this->assertStringNotContainsString('EventSubscription.SceneItemTransformChanged', $agentSource);
        $this->assertStringContainsString('REQUIRED_EVENT_SUBSCRIPTIONS', $agentSource);
        $this->assertStringContainsString('NON_AUDIO_INPUT_KINDS', $agentSource);
        $this->assertStringContainsString('RequestBatchExecutionType.SerialRealtime', $agentSource);
        $this->assertStringContainsString('startObsKeepalive()', $agentSource);
        $this->assertStringContainsString('this.volumePushTimer', $agentSource);

        // Single-studio token must also block a second PC from taking over an active lease
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create([
            'status' => 'offline',
            'last_seen_at' => null,
        ]);

        $auth = ['Authorization' => "Bearer {$studio->agent_token}"];

        $this->withHeaders($auth)
            ->postJson('/api/obs-agent/state', [
                'agent_instance_id' => 'studio-pc-primary',
                'status' => 'online',
                'live_state' => ['currentProgramScene' => 'Main'],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'online');

        $this->withHeaders($auth)
            ->postJson('/api/obs-agent/state', [
                'agent_instance_id' => 'studio-pc-secondary',
                'status' => 'online',
                'live_state' => ['currentProgramScene' => 'Hijack'],
            ])
            ->assertOk()
            ->assertJsonPath('ignored_duplicate_agent', true)
            ->assertJsonPath('active_on_other_agent', true);

        $this->assertSame('Main', $studio->fresh()->getCurrentProgramScene());
    }

    public function test_deleting_collection_or_replacing_backgrounds_deletes_server_files_automatically(): void
    {
        Storage::fake('public');
        Event::fake([ScreenCollectionUpdated::class]);

        $user = User::factory()->create();
        $studio = ObsStudio::factory()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id]);
        $viewA = ScreenView::factory()->create([
            'screen_collection_id' => $collection->id,
            'user_id' => $user->id,
            'name' => 'View A',
            'sort_order' => 1,
        ]);
        $viewB = ScreenView::factory()->create([
            'screen_collection_id' => $collection->id,
            'user_id' => $user->id,
            'name' => 'View B',
            'sort_order' => 2,
        ]);
        $collection->update(['active_view_id' => $viewA->id]);

        // Upload 3 assets
        $upload = function (string $name) use ($user, $studio, $collection): ScreenAsset {
            $res = $this->actingAs($user)
                ->post(route('studio.collections.assets.store', [
                    'studio' => $studio->slug,
                    'collection' => $collection->id,
                ]), [
                    'file' => UploadedFile::fake()->image($name, 120, 120),
                    'type' => 'image',
                ], ['Accept' => 'application/json'])
                ->assertCreated();

            return ScreenAsset::query()->findOrFail((int) $res->json('asset.id'));
        };

        $bgOld = $upload('bg-old.png');
        $bgNew = $upload('bg-new.png');
        $sharedLogo = $upload('shared-logo.png');

        Storage::disk('public')->assertExists($bgOld->path);
        Storage::disk('public')->assertExists($bgNew->path);
        Storage::disk('public')->assertExists($sharedLogo->path);

        // Attach bgOld to collection default theme, and sharedLogo to BOTH viewA and viewB
        $this->actingAs($user)
            ->putJson(route('studio.collections.update', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'theme' => [
                    'background_mode' => 'asset',
                    'background_asset_id' => $bgOld->id,
                ],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->putJson(route('studio.collections.views.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $viewA->id,
            ]), [
                'background_mode' => 'asset',
                'background_asset_id' => $sharedLogo->id,
                'layers' => [],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->putJson(route('studio.collections.views.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $viewB->id,
            ]), [
                'background_mode' => 'theme',
                'layers' => [[
                    'id' => 'lyr_logo',
                    'type' => 'asset',
                    'name' => 'Shared Logo',
                    'props' => ['asset_id' => $sharedLogo->id, 'url' => $sharedLogo->getUrl(false)],
                ]],
            ])
            ->assertOk();

        // Replace bgOld with bgNew -> bgOld is unreferenced elsewhere, so its DB row AND server file are auto-deleted!
        $replaceRes = $this->actingAs($user)
            ->putJson(route('studio.collections.update', ['studio' => $studio->slug, 'collection' => $collection->id]), [
                'theme' => [
                    'background_mode' => 'asset',
                    'background_asset_id' => $bgNew->id,
                ],
            ])
            ->assertOk();

        $this->assertContains($bgOld->id, $replaceRes->json('pruned_asset_ids'));
        $this->assertDatabaseMissing('obs_screen_assets', ['id' => $bgOld->id]);
        Storage::disk('public')->assertMissing($bgOld->path);
        Storage::disk('public')->assertExists($bgNew->path);

        // Removing sharedLogo from viewA MUST KEEP sharedLogo because viewB still uses it
        $this->actingAs($user)
            ->putJson(route('studio.collections.views.update', [
                'studio' => $studio->slug,
                'collection' => $collection->id,
                'view' => $viewA->id,
            ]), [
                'background_mode' => 'theme',
                'background_asset_id' => null,
                'layers' => [],
            ])
            ->assertOk();

        $this->assertDatabaseHas('obs_screen_assets', ['id' => $sharedLogo->id]);
        Storage::disk('public')->assertExists($sharedLogo->path);

        // Deleting the entire collection removes all remaining owned assets AND their physical files from server storage
        $this->actingAs($user)
            ->deleteJson(route('studio.collections.destroy', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk();

        $this->assertDatabaseMissing('obs_screen_assets', ['id' => $bgNew->id]);
        $this->assertDatabaseMissing('obs_screen_assets', ['id' => $sharedLogo->id]);
        Storage::disk('public')->assertMissing($bgNew->path);
        Storage::disk('public')->assertMissing($sharedLogo->path);
    }

    public function test_bulk_cleaner_endpoint_and_filament_studio_settings_remove_unused_and_orphaned_files_safely(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([ScreenCollectionUpdated::class]);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $studio = ObsStudio::factory()->for($owner)->create();
        $collection = ScreenCollection::factory()->for($owner)->create(['obs_studio_id' => $studio->id]);
        $view = ScreenView::factory()->create([
            'screen_collection_id' => $collection->id,
            'user_id' => $owner->id,
            'name' => 'Active Stage',
        ]);

        // Create 1 active asset, 2 unused assets, 1 orphaned disk file for owner, and 1 asset for another user
        $activePath = "screen-assets/{$owner->id}/active-banner.png";
        $unusedPath1 = "screen-assets/{$owner->id}/unused-1.png";
        $unusedPath2 = "screen-assets/{$owner->id}/unused-2.png";
        $orphanPath = "screen-assets/{$owner->id}/orphan-ghost.png";
        $otherUserPath = "screen-assets/{$otherUser->id}/foreign-file.png";

        Storage::disk('public')->put($activePath, str_repeat('A', 2048));
        Storage::disk('public')->put($unusedPath1, str_repeat('B', 4096));
        Storage::disk('public')->put($unusedPath2, str_repeat('C', 3072));
        Storage::disk('public')->put($orphanPath, str_repeat('D', 1024));
        Storage::disk('public')->put($otherUserPath, str_repeat('E', 1024));

        $activeAsset = ScreenAsset::factory()->create([
            'user_id' => $owner->id,
            'screen_collection_id' => $collection->id,
            'name' => 'Active Banner',
            'path' => $activePath,
            'size_bytes' => 2048,
        ]);
        $unusedAsset1 = ScreenAsset::factory()->create([
            'user_id' => $owner->id,
            'screen_collection_id' => $collection->id,
            'name' => 'Unused Graphic 1',
            'path' => $unusedPath1,
            'size_bytes' => 4096,
        ]);
        $unusedAsset2 = ScreenAsset::factory()->create([
            'user_id' => $owner->id,
            'screen_collection_id' => $collection->id,
            'name' => 'Unused Graphic 2',
            'path' => $unusedPath2,
            'size_bytes' => 3072,
        ]);
        $otherUserAsset = ScreenAsset::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Foreign File',
            'path' => $otherUserPath,
            'size_bytes' => 1024,
        ]);

        $view->update([
            'background_mode' => 'asset',
            'background_asset_id' => $activeAsset->id,
        ]);

        // Filament StudioSettings Livewire page surfaces unused files and supports selective & bulk deletion
        $this->actingAs($owner);
        Livewire::test(StudioSettings::class)
            ->assertOk()
            ->assertSee('Server Media & Bulk Unused File Cleaner')
            ->assertSee('Unused Graphic 1')
            ->assertSee('Unused Graphic 2')
            ->assertSee($orphanPath)
            ->assertDontSee('Active Banner')
            ->assertDontSee('Foreign File')
            ->set('selectedUnusedAssetIds', [$unusedAsset1->id])
            ->call('cleanSelectedUnusedAssets')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('obs_screen_assets', ['id' => $unusedAsset1->id]);
        Storage::disk('public')->assertMissing($unusedPath1);
        $this->assertDatabaseHas('obs_screen_assets', ['id' => $unusedAsset2->id]);
        $this->assertDatabaseHas('obs_screen_assets', ['id' => $activeAsset->id]);

        // Canvas Builder bulk cleanup endpoint removes remaining unused asset + orphaned disk file, keeps active & other user
        $this->actingAs($otherUser)
            ->postJson(route('studio.collections.assets.cleanup', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertForbidden();

        $cleanupRes = $this->actingAs($owner)
            ->postJson(route('studio.collections.assets.cleanup', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk();

        $cleanupRes->assertJsonPath('summary.deleted_assets', 1);
        $cleanupRes->assertJsonPath('summary.deleted_orphans', 1);
        $cleanupRes->assertJsonPath('summary.total_deleted', 2);

        $this->assertDatabaseMissing('obs_screen_assets', ['id' => $unusedAsset2->id]);
        Storage::disk('public')->assertMissing($unusedPath2);
        Storage::disk('public')->assertMissing($orphanPath);

        // Active asset and other user's file are untouched
        $this->assertDatabaseHas('obs_screen_assets', ['id' => $activeAsset->id]);
        Storage::disk('public')->assertExists($activePath);
        $this->assertDatabaseHas('obs_screen_assets', ['id' => $otherUserAsset->id]);
        Storage::disk('public')->assertExists($otherUserPath);
    }

    public function test_offline_direct_media_upload_cleans_up_local_staging_file(): void
    {
        Storage::fake('local');
        $tempDir = sys_get_temp_dir().'/obsisstant-direct-clean-'.Str::random(6);
        File::ensureDirectoryExists($tempDir);

        try {
            $owner = User::factory()->create(['media_storage_path' => $tempDir]);
            $studio = ObsStudio::factory()->for($owner)->create(['status' => 'offline']);

            $this->actingAs($owner)
                ->postJson(route('studio.media-upload', ['studio' => $studio->slug]), [
                    'image' => UploadedFile::fake()->image('clean-stage.png', 64, 64),
                ])
                ->assertOk()
                ->assertJsonPath('mode', 'direct');

            $this->assertSame([], Storage::disk('local')->allFiles('obs-media-staging'));
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    public function test_all_delete_and_remove_controls_include_confirmation_dialogs_across_studio_builder_and_deck(): void
    {
        $user = User::factory()->create();
        $studio = ObsStudio::factory()->online()->for($user)->create();
        $collection = ScreenCollection::factory()->for($user)->create(['obs_studio_id' => $studio->id]);
        $view = ScreenView::factory()->create(['screen_collection_id' => $collection->id, 'user_id' => $user->id]);
        $collection->update(['active_view_id' => $view->id]);

        $this->actingAs($user)
            ->get(route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => $collection->id]))
            ->assertOk()
            ->assertSee('id="builder-confirm-dialog"', false)
            ->assertSee('confirmBuilderAction', false)
            ->assertSee('id="btn-clean-unused-assets"', false)
            ->assertDontSee('obs-reverb-pill', false);

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('id="delete-scene-dialog"', false)
            ->assertSee('id="delete-source-dialog"', false)
            ->assertSee('id="remove-scene-item-dialog"', false)
            ->assertSee('id="confirm-remove-scene-item-btn"', false)
            ->assertDontSee('obs-reverb-pill', false);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $studio->slug]))
            ->assertOk()
            ->assertSee('id="deck-confirm-dialog"', false)
            ->assertSee('confirmDeckAction', false)
            ->assertDontSee('obs-reverb-pill', false);
    }

    public function test_filament_navigation_groups_are_cleanly_categorized_and_rendered_without_sort_collisions(): void
    {
        $this->assertSame('Live Control', ObsControlDeck::getNavigationGroup());
        $this->assertSame(3, ObsControlDeck::getNavigationSort());

        $this->assertSame('Stage & Overlays', ScreenCollectionResource::getNavigationGroup());
        $this->assertSame(1, ScreenCollectionResource::getNavigationSort());
        $this->assertSame('Stage & Overlays', ObsScreenResource::getNavigationGroup());
        $this->assertSame(2, ObsScreenResource::getNavigationSort());

        $this->assertSame('Studio & Automation', ObsStudioResource::getNavigationGroup());
        $this->assertSame(1, ObsStudioResource::getNavigationSort());
        $this->assertSame('Studio & Automation', ObsMacroResource::getNavigationGroup());
        $this->assertSame(2, ObsMacroResource::getNavigationSort());
        $this->assertSame('Studio & Automation', StudioSettings::getNavigationGroup());
        $this->assertSame(3, StudioSettings::getNavigationSort());

        $this->assertSame('Documentation', KnowledgeBase::getNavigationGroup());
        $this->assertSame(1, KnowledgeBase::getNavigationSort());

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Live Control')
            ->assertSee('Stage &amp; Overlays', false)
            ->assertSee('Studio &amp; Automation', false)
            ->assertSee('Documentation')
            ->assertSee('obs-reverb-pill', false);
    }
}
