<?php

namespace App\Filament\SuperAdmin\Resources\Subscriptions;

use App\Filament\SuperAdmin\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Package;
use App\Models\Subscription;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Management';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        $featureOverrides = [];
        foreach (Package::featureCatalog() as $key => $label) {
            $featureOverrides[] = Select::make("feature_overrides.{$key}")
                ->label($label)
                ->options([
                    '1' => 'Force Enable',
                    '0' => 'Force Disable',
                ])
                ->placeholder('Inherit from package')
                ->nullable();
        }

        $limitOverrides = [];
        foreach (Package::limitCatalog() as $key => $label) {
            $limitOverrides[] = TextInput::make("limit_overrides.{$key}")
                ->label($label)
                ->numeric()
                ->minValue(-1)
                ->nullable()
                ->placeholder('Inherit from package')
                ->helperText('Leave blank to inherit; -1 = unlimited.');
        }

        return $schema
            ->components([
                Section::make('Subscription Assignment')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('package_id')
                            ->label('Package')
                            ->relationship('package', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }
                                $pkg = Package::query()->find((int) $state);
                                if ($pkg?->billing_cycle === 'lifetime') {
                                    $set('ends_at', null);
                                }
                            })
                            ->required(),
                        Select::make('status')
                            ->options(Subscription::statusOptions())
                            ->default(Subscription::STATUS_ACTIVE)
                            ->required(),
                        DateTimePicker::make('starts_at')
                            ->default(now()),
                        DateTimePicker::make('ends_at')
                            ->label('Expires At')
                            ->helperText('Leave blank for lifetime / no expiry.'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Per-Client Feature Overrides (Optional)')
                    ->description('Override specific modules for this customer without changing their package.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->schema($featureOverrides),

                Section::make('Per-Client Quota Overrides (Optional)')
                    ->description('Override specific numeric limits for this customer.')
                    ->columnSpanFull()
                    ->columns(3)
                    ->collapsed()
                    ->schema($limitOverrides),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING => 'success',
                        Subscription::STATUS_PAST_DUE => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Expires')
                    ->dateTime('M j, Y')
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
        ];
    }
}
