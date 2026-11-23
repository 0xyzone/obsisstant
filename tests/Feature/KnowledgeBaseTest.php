<?php

namespace Tests\Feature;

use App\Filament\Pages\KnowledgeBase;
use App\Models\User;
use App\Services\ChangelogService;
use App\Services\KnowledgeBaseService;
use App\Services\ReverbStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ReverbStatusService::resetFake();

        parent::tearDown();
    }

    public function test_knowledge_base_service_parses_all_modular_markdown_guides_in_order(): void
    {
        $articles = KnowledgeBaseService::getArticles();

        $this->assertCount(5, $articles);
        $this->assertSame([
            'getting-started',
            'control-deck',
            'browser-overlays',
            'broadcast-macros',
            'troubleshooting',
        ], array_column($articles, 'slug'));

        $this->assertNotEmpty($articles[0]['html']);
        $this->assertStringContainsString('<h2 id="getting-started-', $articles[0]['html']);
    }

    public function test_public_knowledge_base_renders_articles_command_reference_and_header_badges(): void
    {
        ReverbStatusService::fakeUsing(static fn (): bool => true);
        $versionTag = 'v'.ChangelogService::getCurrentVersion();

        $this->get(route('knowledge-base'))
            ->assertOk()
            ->assertSee('Obsisstant Knowledge Base')
            ->assertSee($versionTag)
            ->assertSee('Reverb active')
            ->assertSee('Getting Started & Relay Pairing')
            ->assertSee('OBS WebSocket v5 Request Reference')
            ->assertSee('SetCurrentProgramScene')
            ->assertSee('SetInputMute')
            ->assertSee('SetSceneItemEnabled');
    }

    public function test_filament_knowledge_base_page_requires_auth_and_renders_inside_admin_panel(): void
    {
        $this->get('/admin/knowledge-base')->assertRedirect();

        ReverbStatusService::fakeUsing(static fn (): bool => false);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/knowledge-base')
            ->assertOk()
            ->assertSee('Obsisstant Knowledge Base')
            ->assertSee('Reverb offline')
            ->assertSee('SetCurrentProgramScene');

        Livewire::actingAs($user)
            ->test(KnowledgeBase::class)
            ->assertOk()
            ->assertSee('Broadcast Macros & Automation');
    }
}
