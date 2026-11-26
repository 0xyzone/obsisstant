<?php

namespace App\Services\Saas;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class EntitlementService
{
    public function activeSubscription(User $user): ?Subscription
    {
        $subscription = $user->subscriptions()
            ->with('package')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->orderByDesc('id')
            ->first();

        if (! $subscription instanceof Subscription || ! $subscription->isActive()) {
            return null;
        }

        return $subscription;
    }

    public function hasActiveAccess(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $this->activeSubscription($user) instanceof Subscription;
    }

    public function hasFeature(User $user, string $feature): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $subscription = $this->activeSubscription($user);
        if (! $subscription instanceof Subscription) {
            return false;
        }

        $overrides = is_array($subscription->feature_overrides) ? $subscription->feature_overrides : [];
        if (array_key_exists($feature, $overrides) && $overrides[$feature] !== null) {
            return (bool) $overrides[$feature];
        }

        $packageFeatures = is_array($subscription->package?->features) ? $subscription->package->features : [];

        return (bool) ($packageFeatures[$feature] ?? false);
    }

    /**
     * Return the numeric quota for a limit key, or null when unlimited.
     */
    public function limitFor(User $user, string $limitKey): ?int
    {
        if ($user->is_super_admin) {
            return null;
        }

        $subscription = $this->activeSubscription($user);
        if (! $subscription instanceof Subscription) {
            return 0;
        }

        $overrides = is_array($subscription->limit_overrides) ? $subscription->limit_overrides : [];
        if (array_key_exists($limitKey, $overrides) && $overrides[$limitKey] !== null && $overrides[$limitKey] !== '') {
            $val = (int) $overrides[$limitKey];

            return $val < 0 ? null : $val;
        }

        $packageLimits = is_array($subscription->package?->limits) ? $subscription->package->limits : [];
        if (! array_key_exists($limitKey, $packageLimits) || $packageLimits[$limitKey] === null || $packageLimits[$limitKey] === '') {
            return null;
        }

        $val = (int) $packageLimits[$limitKey];

        return $val < 0 ? null : $val;
    }

    public function canCreate(User $user, string $limitKey, int $currentCount): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if (! $this->hasActiveAccess($user)) {
            return false;
        }

        $limit = $this->limitFor($user, $limitKey);
        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }

    public function consumeOxyAiQuota(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if (! $this->hasFeature($user, 'oxy_gemini_ai')) {
            return false;
        }

        $limit = $this->limitFor($user, 'oxy_daily_messages');
        if ($limit === null) {
            return true;
        }

        if ($limit <= 0) {
            return false;
        }

        $key = 'oxy_ai_daily_'.$user->id.'_'.now()->toDateString();
        $used = (int) Cache::get($key, 0);
        if ($used >= $limit) {
            return false;
        }

        Cache::put($key, $used + 1, now()->endOfDay());

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function summaryFor(User $user): array
    {
        $subscription = $this->activeSubscription($user);
        $package = $subscription?->package;

        $features = [];
        foreach (array_keys(Package::featureCatalog()) as $key) {
            $features[$key] = $this->hasFeature($user, $key);
        }

        $limits = [];
        foreach (array_keys(Package::limitCatalog()) as $key) {
            $limits[$key] = $this->limitFor($user, $key);
        }

        return [
            'active' => $this->hasActiveAccess($user),
            'is_super_admin' => (bool) $user->is_super_admin,
            'package_name' => $user->is_super_admin ? 'Super Admin' : ($package?->name ?? 'No Active Plan'),
            'status' => $subscription?->status ?? ($user->is_super_admin ? 'active' : 'inactive'),
            'ends_at' => $subscription?->ends_at?->toDateString(),
            'features' => $features,
            'limits' => $limits,
        ];
    }
}
