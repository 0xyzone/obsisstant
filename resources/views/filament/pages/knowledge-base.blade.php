<x-filament-panels::page>
    <x-knowledge-base-styles />

    <x-knowledge-base-workspace
        :articles="$this->getArticles()"
        :commandReference="$this->getCommandReference()"
        :activeSlug="request()->query('topic', '')"
        :macroCreateUrl="\App\Filament\Resources\ObsMacros\ObsMacroResource::getUrl('create')"
        :isFilament="true"
    />
</x-filament-panels::page>
