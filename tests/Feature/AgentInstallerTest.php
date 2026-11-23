<?php

namespace Tests\Feature;

use App\Models\ObsStudio;
use App\Models\User;
use App\Services\ReverbStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class AgentInstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        ReverbStatusService::resetFake();

        parent::tearDown();
    }

    public function test_authenticated_user_can_download_mac_and_windows_agent_installers_with_embedded_credentials(): void
    {
        $user = User::factory()->create();
        $relayToken = $user->getOrCreateRelayToken();

        $macResponse = $this->actingAs($user)
            ->get(route('agent-installer.download', ['platform' => 'mac']));

        $macResponse->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        $macZipPath = $macResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($macZipPath) === true);

        $envContent = $zip->getFromName('Obsisstant-OBS-Agent/agent/.env');
        $this->assertIsString($envContent);
        $this->assertStringContainsString("OBSISSTANT_RELAY_TOKEN={$relayToken}", $envContent);
        $this->assertStringContainsString('OBSISSTANT_URL='.rtrim((string) config('app.url'), '/'), $envContent);

        $this->assertNotFalse($zip->getFromName('Obsisstant-OBS-Agent/agent/src/index.js'));
        $this->assertNotFalse($zip->getFromName('Obsisstant-OBS-Agent/agent/package.json'));

        $installScript = $zip->getFromName('Obsisstant-OBS-Agent/Install & Run Obsisstant Agent.command');
        $this->assertIsString($installScript);
        $this->assertStringContainsString('LaunchAgents', $installScript);
        $this->assertStringContainsString('com.obsisstant.agent.plist', $installScript);

        $opsys = 0;
        $attr = 0;
        $zip->getExternalAttributesName('Obsisstant-OBS-Agent/Install & Run Obsisstant Agent.command', $opsys, $attr);
        $this->assertSame(ZipArchive::OPSYS_UNIX, $opsys);
        $this->assertSame(0100755, ($attr >> 16) & 0177777);

        $this->assertNotFalse($zip->getFromName('Obsisstant-OBS-Agent/Uninstall Obsisstant Agent.command'));
        $zip->close();

        $winResponse = $this->actingAs($user)
            ->get(route('agent-installer.download', ['platform' => 'windows']));

        $winResponse->assertOk();
        $winZipPath = $winResponse->getFile()->getPathname();
        $winZip = new ZipArchive;
        $this->assertTrue($winZip->open($winZipPath) === true);

        $winBat = $winZip->getFromName('Obsisstant-OBS-Agent/Install & Run Obsisstant Agent.bat');
        $winPs1 = $winZip->getFromName('Obsisstant-OBS-Agent/install-agent.ps1');
        $winUninstall = $winZip->getFromName('Obsisstant-OBS-Agent/Uninstall Obsisstant Agent.bat');
        $winEnv = $winZip->getFromName('Obsisstant-OBS-Agent/agent/.env');

        $this->assertIsString($winBat);
        $this->assertStringContainsString('install-agent.ps1', $winBat);
        $this->assertIsString($winPs1);
        $this->assertStringContainsString('OpenJS.NodeJS.LTS', $winPs1);
        $this->assertStringContainsString('Obsisstant OBS Agent.lnk', $winPs1);
        $this->assertIsString($winUninstall);
        $this->assertIsString($winEnv);
        $this->assertStringContainsString("OBSISSTANT_RELAY_TOKEN={$relayToken}", $winEnv);
        $winZip->close();
    }

    public function test_studio_scoped_installer_uses_studio_agent_token_and_blocks_foreign_users(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $studio = ObsStudio::factory()->for($owner)->create(['agent_token' => 'obs_studio_secret_token_999']);

        $this->get(route('agent-installer.download', ['platform' => 'mac']))
            ->assertRedirect('/admin/login');

        $this->actingAs($intruder)
            ->get(route('agent-installer.download', ['platform' => 'mac', 'studio' => $studio->slug]))
            ->assertForbidden();

        $response = $this->actingAs($owner)
            ->get(route('agent-installer.download', ['platform' => 'mac', 'studio' => $studio->slug]));

        $response->assertOk();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->getFile()->getPathname()) === true);
        $envContent = $zip->getFromName('Obsisstant-OBS-Agent/agent/.env');
        $this->assertIsString($envContent);
        $this->assertStringContainsString('OBSISSTANT_AGENT_TOKEN=obs_studio_secret_token_999', $envContent);
        $this->assertStringNotContainsString('OBSISSTANT_RELAY_TOKEN=', $envContent);
        $zip->close();
    }

    public function test_one_click_bootstrap_setup_scripts_and_bundle_endpoints_work_and_expire(): void
    {
        $user = User::factory()->create();

        $tokenRes = $this->actingAs($user)
            ->postJson(route('agent-installer.bootstrap-token'))
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'expires_in_minutes',
                'mac_command',
                'windows_command',
                'mac_download_url',
                'windows_download_url',
            ]);

        $token = $tokenRes->json('token');
        $this->assertStringContainsString("/setup-agent/{$token}/mac.sh", (string) $tokenRes->json('mac_command'));
        $this->assertStringContainsString("/setup-agent/{$token}/win.ps1", (string) $tokenRes->json('windows_command'));

        auth()->logout();

        $this->get(route('agent-installer.setup-script', ['token' => $token, 'script' => 'mac.sh']))
            ->assertOk()
            ->assertSee("/setup-agent/{$token}/bundle/mac.zip", false);

        $this->get(route('agent-installer.setup-script', ['token' => $token, 'script' => 'win.ps1']))
            ->assertOk()
            ->assertSee("/setup-agent/{$token}/bundle/windows.zip", false);

        $bundleRes = $this->get(route('agent-installer.setup-bundle', ['token' => $token, 'platform' => 'windows']));
        $bundleRes->assertOk();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($bundleRes->getFile()->getPathname()) === true);
        $this->assertNotFalse($zip->getFromName('Obsisstant-OBS-Agent/install-agent.ps1'));
        $zip->close();

        $this->get(route('agent-installer.setup-script', ['token' => str_repeat('z', 48), 'script' => 'mac.sh']))
            ->assertNotFound();
    }

    public function test_offline_dashboard_and_control_center_prompt_to_install_agent_when_disconnected(): void
    {
        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $user = User::factory()->create();
        $neverConnectedStudio = ObsStudio::factory()->for($user)->create([
            'status' => 'offline',
            'last_seen_at' => null,
        ]);

        // Dashboard renders Install OBS Agent onboarding prompt when no studio is online
        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('obs-dashboard-install-prompt', false)
            ->assertSee('OBS Agent is not installed or running yet')
            ->assertSee('Download for Mac (.zip)')
            ->assertSee('Download for Windows (.zip)');

        $this->actingAs($user)
            ->get('/admin/obs-studios')
            ->assertOk()
            ->assertSee('Pair OBS Agent')
            ->assertSee('Install Agent');

        // Control Center shows Connect OBS banner & modal when agent is not detected
        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $neverConnectedStudio->slug]))
            ->assertOk()
            ->assertSee('install-agent-dialog', false)
            ->assertSee('Connect OBS Studio on your Mac or Windows PC')
            ->assertSee('agent-offline-banner', false);

        $this->actingAs($user)
            ->getJson(route('studio.snapshot', ['studio' => $neverConnectedStudio->slug]))
            ->assertOk()
            ->assertJsonPath('agent_reachable', false);

        // When obs-agent heartbeat is active but OBS desktop app is closed, show waiting-for-OBS state instead
        $agentRunningStudio = ObsStudio::factory()->for($user)->create([
            'status' => 'offline',
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $agentRunningStudio->slug]))
            ->assertOk()
            ->assertSee('OBS Agent connected — open OBS Studio');

        $this->actingAs($user)
            ->getJson(route('studio.snapshot', ['studio' => $agentRunningStudio->slug]))
            ->assertOk()
            ->assertJsonPath('agent_reachable', true);
    }
}
