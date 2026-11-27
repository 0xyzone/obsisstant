<?php

namespace App\Filament\SuperAdmin\Resources\Users;

use App\Filament\SuperAdmin\Resources\Users\Pages\ListUsers;
use App\Models\Package;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Management';

    protected static ?string $navigationLabel = 'Customers & Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
                        TextInput::make('contact_number')
                            ->maxLength(30),
                        TextInput::make('current_address')
                            ->maxLength(255),
                        Toggle::make('is_super_admin')
                            ->label('Super Admin (full access to /hq)')
                            ->default(false),
                    ]),

                Section::make('Initial Subscription (On Create)')
                    ->description('Assign a purchased package immediately when creating this customer.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->visibleOn('create')
                    ->schema([
                        Select::make('initial_package_id')
                            ->label('Package')
                            ->options(fn (): array => Package::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (blank($state)) {
                                    return;
                                }
                                $pkg = Package::query()->find((int) $state);
                                if ($pkg?->billing_cycle === 'lifetime') {
                                    $set('initial_never_expires', true);
                                    $set('initial_ends_at', null);
                                }
                            })
                            ->nullable(),
                        Toggle::make('initial_never_expires')
                            ->label('Lifetime / Never Expires')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set, bool $state): void {
                                if ($state) {
                                    $set('initial_ends_at', null);
                                }
                            }),
                        DateTimePicker::make('initial_ends_at')
                            ->label('Expires At')
                            ->default(now()->addMonth())
                            ->hidden(fn (Get $get): bool => (bool) $get('initial_never_expires'))
                            ->helperText('Leave blank or toggle Lifetime for no expiration.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('latestSubscription.package.name')
                    ->label('Package')
                    ->badge()
                    ->placeholder('None'),
                TextColumn::make('latestSubscription.status')
                    ->label('Plan Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active', 'trialing' => 'success',
                        'past_due' => 'warning',
                        null => 'gray',
                        default => 'danger',
                    })
                    ->placeholder('No Plan'),
                IconColumn::make('is_super_admin')
                    ->label('Super Admin')
                    ->boolean(),
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
            'index' => ListUsers::route('/'),
        ];
    }
}
