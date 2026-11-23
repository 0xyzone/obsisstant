<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    public function test_changelog_page_renders_successfully_with_initial_release(): void
    {
        $response = $this->get('/changelog');

        $response->assertStatus(200);
        $response->assertSee('v1.0.0');
        $response->assertSee('Product Releases');
        $response->assertSee('Obsisstant');
    }
}
