<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentSubscriptionsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Customer Subscriptions';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Subscription::query()
                    ->with(['user', 'package'])
                    ->latest('id')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->weight('bold'),
                TextColumn::make('user.email')
                    ->label('Email'),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING => 'success',
                        Subscription::STATUS_PAST_DUE => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('ends_at')
                    ->label('Expires')
                    ->dateTime('M j, Y')
                    ->placeholder('Never'),
            ])
            ->paginated(false);
    }
}
