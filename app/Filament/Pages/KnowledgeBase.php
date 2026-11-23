<?php

namespace App\Filament\Pages;

use App\Services\KnowledgeBaseService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class KnowledgeBase extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'OBS Studio';

    protected static ?string $navigationLabel = 'Knowledge Base';

    protected static ?string $title = 'Operator Knowledge Base & OBS Reference';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.knowledge-base';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getArticles(): array
    {
        return KnowledgeBaseService::getArticles();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getCommandReference(): array
    {
        return KnowledgeBaseService::getCommandReference();
    }
}
