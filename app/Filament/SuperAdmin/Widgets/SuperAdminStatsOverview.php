<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $customersCount = User::query()->where('is_super_admin', false)->count();
        $superAdminsCount = User::query()->where('is_super_admin', true)->count();

        $activeSubs = Subscription::query()
            ->with('package')
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->get()
            ->filter(fn (Subscription $sub): bool => $sub->isActive());

        $expiredOrSuspended = Subscription::query()
            ->whereIn('status', [Subscription::STATUS_EXPIRED, Subscription::STATUS_SUSPENDED, Subscription::STATUS_PAST_DUE])
            ->count();

        $monthlyValueNpr = $activeSubs->sum(function (Subscription $sub): float {
            $package = $sub->package;
            if (! $package instanceof Package || $package->currency !== 'NPR') {
                return 0.0;
            }

            $amount = $package->price_cents / 100;

            return match ($package->billing_cycle) {
                'yearly' => $amount / 12,
                'lifetime' => 0.0,
                default => $amount,
            };
        });

        $activePackages = Package::query()->where('is_active', true)->count();

        return [
            Stat::make('Total Customers', (string) $customersCount)
                ->description("{$superAdminsCount} super admin(s)")
                ->color('primary'),
            Stat::make('Active Subscriptions', (string) $activeSubs->count())
                ->description("{$expiredOrSuspended} expired / suspended")
                ->color($activeSubs->count() > 0 ? 'success' : 'gray'),
            Stat::make('Monthly Value (NPR)', 'NPR '.number_format($monthlyValueNpr, 0))
                ->description('Estimated active MRR')
                ->color('primary'),
            Stat::make('Active Packages', (string) $activePackages)
                ->description('Modular plans configured')
                ->color('gray'),
        ];
    }
}
