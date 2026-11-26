<?php

namespace App\Filament\SuperAdmin\Resources\DemoRequests;

use App\Filament\SuperAdmin\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Models\DemoRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DemoRequestResource extends Resource
{
    protected static ?string $model = DemoRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Management';

    protected static ?string $navigationLabel = 'Demo Requests';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Demo Applicant')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        TextInput::make('email')->email()->required()->maxLength(180),
                        TextInput::make('phone')->maxLength(40),
                        TextInput::make('channel_or_org')->label('Channel / Organization')->maxLength(150),
                        Select::make('package_id')
                            ->label('Preferred Package')
                            ->relationship('package', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('status')
                            ->options(DemoRequest::statusOptions())
                            ->default(DemoRequest::STATUS_PENDING)
                            ->required(),
                        Textarea::make('message')
                            ->label('Applicant Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('admin_notes')
                            ->label('Internal Admin Notes')
                            ->rows(3)
                            ->columnSpanFull(),
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
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->badge()
                    ->placeholder('Undecided'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        DemoRequest::STATUS_APPROVED => 'success',
                        DemoRequest::STATUS_CONTACTED => 'warning',
                        DemoRequest::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M j, Y H:i')
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
            'index' => ListDemoRequests::route('/'),
        ];
    }
}
