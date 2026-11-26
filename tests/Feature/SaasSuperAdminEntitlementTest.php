<?php

namespace Tests\Feature;

use App\Filament\Resources\ObsStudios\ObsStudioResource;
use App\Models\ObsStudio;
use App\Models\Package;
use App\Models\ScreenCollection;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Saas\EntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasSuperAdminEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled_and_super_admin_panel_is_gated(): void
    {
        $this->get('/admin/register')->assertNotFound();

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Studio Operator')
            ->assertSee('Sign in to Studio');

        $this->get('/hq/login')
            ->assertOk()
            ->assertSee('Super Admin HQ')
            ->assertSee('Sign in to Obsisstant HQ');

        $customer = User::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($customer)->get('/hq')->assertForbidden();
        $this->actingAs($customer)->get('/hq/packages')->assertForbidden();
        $this->actingAs($customer)->get('/hq/users')->assertForbidden();
        $this->actingAs($customer)->get('/hq/subscriptions')->assertForbidden();

        $this->actingAs($superAdmin)->get('/hq')
            ->assertOk()
            ->assertSee('Total Customers')
            ->assertSee('Monthly Value (NPR)')
            ->assertSee('Recent Customer Subscriptions');
        $this->actingAs($superAdmin)->get('/hq/packages')->assertOk();
        $this->actingAs($superAdmin)->get('/hq/users')->assertOk();
        $this->actingAs($superAdmin)->get('/hq/subscriptions')->assertOk();
    }

    public function test_entitlement_service_enforces_module_toggles_numeric_quotas_overrides_and_expiry(): void
    {
        $entitlements = app(EntitlementService::class);

        $starter = Package::factory()->create([
            'name' => 'Starter',
            'slug' => 'starter-test',
            'features' => [
                'obs_control' => true,
                'unified_stage' => true,
                'canvas_builder' => false,
                'mobile_deck' => false,
                'macros' => false,
                'audio_mixer' => true,
                'oxy_gemini_ai' => true,
            ],
            'limits' => [
                'max_studios' => 1,
                'max_collections' => 1,
                'max_views' => 4,
                'max_macros' => 0,
                'max_storage_mb' => 50,
                'oxy_daily_messages' => 2,
            ],
        ]);

        // Unsubscribed user has no access
        $unsubscribed = User::factory()->withoutSubscription()->create();
        $this->assertFalse($entitlements->hasActiveAccess($unsubscribed));
        $this->assertFalse($entitlements->hasFeature($unsubscribed, 'obs_control'));
        $this->assertSame(0, $entitlements->limitFor($unsubscribed, 'max_studios'));

        // Subscribed Starter customer
        $customer = User::factory()->withoutSubscription()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $customer->id,
            'package_id' => $starter->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertTrue($entitlements->hasActiveAccess($customer));
        $this->assertTrue($entitlements->hasFeature($customer, 'unified_stage'));
        $this->assertFalse($entitlements->hasFeature($customer, 'canvas_builder'));
        $this->assertFalse($entitlements->hasFeature($customer, 'mobile_deck'));

        // Numeric quota checks
        $this->assertTrue($entitlements->canCreate($customer, 'max_studios', 0));
        $this->assertFalse($entitlements->canCreate($customer, 'max_studios', 1));

        // Oxy daily AI quota (2/day)
        $this->assertTrue($entitlements->consumeOxyAiQuota($customer));
        $this->assertTrue($entitlements->consumeOxyAiQuota($customer));
        $this->assertFalse($entitlements->consumeOxyAiQuota($customer));

        // Per-client overrides work without changing the package
        $subscription->update([
            'feature_overrides' => ['canvas_builder' => '1'],
            'limit_overrides' => ['max_studios' => '3'],
        ]);

        $this->assertTrue($entitlements->hasFeature($customer, 'canvas_builder'));
        $this->assertTrue($entitlements->canCreate($customer, 'max_studios', 2));
        $this->assertFalse($entitlements->canCreate($customer, 'max_studios', 3));

        // Expired subscription revokes access
        $subscription->update([
            'status' => Subscription::STATUS_EXPIRED,
            'ends_at' => now()->subMinute(),
        ]);

        $this->assertFalse($entitlements->hasActiveAccess($customer));
        $this->assertFalse($entitlements->hasFeature($customer, 'obs_control'));
    }

    public function test_controllers_and_resources_enforce_package_modules_and_studio_quota(): void
    {
        $limitedPackage = Package::factory()->create([
            'features' => [
                'obs_control' => true,
                'unified_stage' => true,
                'canvas_builder' => false,
                'mobile_deck' => false,
                'macros' => false,
                'audio_mixer' => true,
                'oxy_gemini_ai' => false,
            ],
            'limits' => [
                'max_studios' => 1,
                'max_collections' => 2,
                'max_views' => 5,
                'max_macros' => 0,
                'max_storage_mb' => 50,
                'oxy_daily_messages' => 0,
            ],
        ]);

        $user = User::factory()->withoutSubscription()->create();
        Subscription::factory()->create([
            'user_id' => $user->id,
            'package_id' => $limitedPackage->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->assertTrue(ObsStudioResource::canCreate());

        $studio = ObsStudio::factory()->online()->for($user)->create();
        $this->assertFalse(ObsStudioResource::canCreate());

        $collection = ScreenCollection::create([
            'obs_studio_id' => $studio->id,
            'user_id' => $user->id,
            'name' => 'Test Stage',
        ]);

        $this->get(route('obs.deck.show', ['studio' => $studio->slug]))->assertForbidden();
        $this->get(route('studio.collections.builder', ['studio' => $studio->slug, 'collection' => $collection->id]))->assertForbidden();
    }
}
