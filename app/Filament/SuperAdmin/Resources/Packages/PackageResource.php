<?php

namespace App\Filament\SuperAdmin\Resources\Packages;

use App\Filament\SuperAdmin\Resources\Packages\Pages\CreatePackage;
use App\Filament\SuperAdmin\Resources\Packages\Pages\EditPackage;
use App\Filament\SuperAdmin\Resources\Packages\Pages\ListPackages;
use App\Models\Package;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Management';

    protected static ?string $navigationLabel = 'Packages';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $featureToggles = [];
        foreach (Package::featureCatalog() as $key => $label) {
            $featureToggles[] = Toggle::make("features.{$key}")
                ->label($label)
                ->default(true);
        }

        $limitInputs = [];
        foreach (Package::limitCatalog() as $key => $label) {
            $limitInputs[] = TextInput::make("limits.{$key}")
                ->label($label)
                ->numeric()
                ->minValue(0)
                ->nullable()
                ->placeholder('Unlimited')
                ->helperText('Leave blank for unlimited.');
        }

        return $schema
            ->components([
                Section::make('Package Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?Package $record): void {
                                if ($record === null && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true),
                        TextInput::make('price_cents')
                            ->label('Price (in cents)')
                            ->numeric()
                            ->default(2900)
                            ->required()
                            ->minValue(0)
                            ->helperText('Example: 2900 = $29.00'),
                        Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'lifetime' => 'Lifetime',
                            ])
                            ->default('monthly')
                            ->required(),
                        TextInput::make('currency')
                            ->default('USD')
                            ->maxLength(8)
                            ->required(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Active (available for new subscriptions)')
                            ->default(true),
                    ]),

                Section::make('Modular Feature Toggles')
                    ->description('Enable or disable Obsisstant modules included in this package.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema($featureToggles),

                Section::make('Numeric Quotas')
                    ->description('Set numeric caps for studios, views, macros, storage, and daily Oxy Gemini AI messages.')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema($limitInputs),
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
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (Package $record): string => $record->formattedPrice())
                    ->sortable(),
                TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Subscribers')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
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
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
