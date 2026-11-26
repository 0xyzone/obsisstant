<?php

namespace Tests\Feature;

use App\Models\ObsCommandLog;
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

    public function test_idle_second_pc_heartbeat_and_failed_ack_cannot_downgrade_active_online_studio(): void
    {
        $user = User::factory()->create(['relay_token' => 'usr_multi_pc_relay_token_123']);
        $studio = ObsStudio::factory()->for($user)->create([
            'name' => 'Office OBS',
            'status' => 'offline',
            'last_seen_at' => null,
        ]);

        $authHeaders = ['Authorization' => 'Bearer usr_multi_pc_relay_token_123'];

        // PC A connects OBS and brings Office OBS online
        $this->withHeaders($authHeaders)
            ->postJson('/api/obs-agent/state', [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'office-pc-aaa111',
                'status' => 'online',
                'live_state' => [
                    'currentProgramScene' => 'Obsisstant Stage',
                    'stats' => ['activeFps' => 30.0, 'cpuUsage' => 1.5],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'online');

        $this->assertSame('online', $studio->fresh()->status);
        $this->assertTrue($studio->fresh()->isOnline());

        // PC B (idle relay without OBS open) sends offline heartbeat -> must NOT downgrade Office OBS
        $this->withHeaders($authHeaders)
            ->postJson('/api/obs-agent/heartbeat', [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'macbook-bbb222',
                'status' => 'offline',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'online')
            ->assertJsonPath('active_on_other_agent', true)
            ->assertJsonPath('ignored_stale_offline', true);

        $this->assertSame('online', $studio->fresh()->status);

        // Legacy PC C (older obs-agent without agent_instance_id) sends offline heartbeat -> also ignored
        $this->withHeaders($authHeaders)
            ->postJson('/api/obs-agent/heartbeat', [
                'studio_id' => $studio->id,
                'status' => 'offline',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'online')
            ->assertJsonPath('active_on_other_agent', true)
            ->assertJsonPath('ignored_stale_offline', true);

        $this->assertSame('online', $studio->fresh()->status);

        // PC B tries to push offline state -> rejected because PC A holds the active lease
        $this->withHeaders($authHeaders)
            ->postJson('/api/obs-agent/state', [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'macbook-bbb222',
                'status' => 'offline',
                'live_state' => ['currentProgramScene' => null],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'online')
            ->assertJsonPath('ignored_stale_offline', true);

        $this->assertSame('online', $studio->fresh()->status);

        // Idle PC B receives a command while OBS is closed locally and reports "not currently connected" -> ignored
        $log = ObsCommandLog::query()->create([
            'obs_studio_id' => $studio->id,
            'user_id' => $user->id,
            'command_uuid' => '11111111-2222-4333-8444-555555555555',
            'request_type' => 'obsisstant.RefreshAllState',
            'request_data' => [],
            'status' => 'dispatched',
        ]);

        $this->withHeaders($authHeaders)
            ->postJson("/api/obs-agent/commands/{$log->command_uuid}/ack", [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'macbook-bbb222',
                'status' => 'failed',
                'error_message' => 'OBS Studio WebSocket is not currently connected.',
            ])
            ->assertOk()
            ->assertJsonPath('ignored_idle_agent', true);

        $this->assertSame('dispatched', $log->fresh()->status);

        // Online PC A acknowledges the command as succeeded
        $this->withHeaders($authHeaders)
            ->postJson("/api/obs-agent/commands/{$log->command_uuid}/ack", [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'office-pc-aaa111',
                'status' => 'succeeded',
                'response_data' => ['refreshed' => true],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'succeeded');

        $this->assertSame('succeeded', $log->fresh()->status);

        // A late failed ack from another PC can NEVER overwrite an already-succeeded command
        $this->withHeaders($authHeaders)
            ->postJson("/api/obs-agent/commands/{$log->command_uuid}/ack", [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'macbook-bbb222',
                'status' => 'failed',
                'error_message' => 'Some late relay error',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'succeeded')
            ->assertJsonPath('ignored_stale_failure', true);

        $this->assertSame('succeeded', $log->fresh()->status);

        // When PC A itself closes OBS, its offline state transition succeeds cleanly
        $this->withHeaders($authHeaders)
            ->postJson('/api/obs-agent/state', [
                'studio_id' => $studio->id,
                'agent_instance_id' => 'office-pc-aaa111',
                'status' => 'offline',
                'live_state' => ['currentProgramScene' => 'Obsisstant Stage'],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'offline');

        $this->assertSame('offline', $studio->fresh()->status);
    }

    public function test_unified_screen_mode_hides_agent_banner_and_surfaces_persistent_dismiss_controls(): void
    {
        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $user = User::factory()->create();
        ObsStudio::factory()->for($user)->create([
            'name' => 'Previous PC',
            'status' => 'offline',
            'last_seen_at' => now()->subHour(),
            'control_mode' => 'obs',
        ]);
        $unifiedStudio = ObsStudio::factory()->for($user)->create([
            'name' => 'Office OBS',
            'status' => 'offline',
            'last_seen_at' => null,
            'control_mode' => 'screens',
        ]);

        $controlCenterHtml = $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $unifiedStudio->slug]))
            ->assertOk()
            ->assertSee('agent-banner-dismiss-btn', false)
            ->assertSee('const hasAnyConnectedStudio = true;', false)
            ->assertSee('obs_install_dialog_dismissed_v2', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/id="agent-offline-banner"\s+hidden/', (string) $controlCenterHtml);

        $this->actingAs($user)
            ->get(route('obs.deck.show', ['studio' => $unifiedStudio->slug]))
            ->assertOk()
            ->assertSee('deck-offline-dismiss-btn', false)
            ->assertSee('syncDeckOfflineBanner', false);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('obs-dashboard-install-dismiss', false);
    }

    public function test_relay_bootstrap_reports_active_on_other_agent_and_blocks_duplicate_relay_takeover(): void
    {
        $user = User::factory()->create(['relay_token' => 'usr_dual_obs_relay_token_456']);
        $macStudio = ObsStudio::factory()->for($user)->create([
            'name' => 'Mac OBS',
            'obs_host' => '127.0.0.1',
            'obs_port' => 4455,
            'status' => 'offline',
            'last_seen_at' => null,
        ]);
        $officeStudio = ObsStudio::factory()->for($user)->create([
            'name' => 'Office OBS',
            'obs_host' => '127.0.0.1',
            'obs_port' => 4455,
            'status' => 'offline',
            'last_seen_at' => null,
        ]);

        // PC A connects Mac OBS via relay token
        $this->withHeaders([
            'Authorization' => 'Bearer usr_dual_obs_relay_token_456',
            'X-Obs-Agent-Instance' => 'macbook-pc-111',
        ])->postJson('/api/obs-agent/state', [
            'studio_id' => $macStudio->id,
            'status' => 'online',
            'live_state' => ['currentProgramScene' => 'Mac Scene'],
        ])->assertOk()->assertJsonPath('status', 'online');

        // PC B bootstraps via relay token: sees Mac OBS owned by another agent, Office OBS free
        $pcBBootstrap = $this->withHeaders([
            'Authorization' => 'Bearer usr_dual_obs_relay_token_456',
            'X-Obs-Agent-Instance' => 'office-pc-222',
        ])->getJson('/api/obs-agent/bootstrap')->assertOk();

        $studiosById = collect($pcBBootstrap->json('studios'))->keyBy('studio.id');
        $this->assertTrue((bool) $studiosById[$macStudio->id]['active_on_other_agent']);
        $this->assertFalse((bool) $studiosById[$officeStudio->id]['active_on_other_agent']);

        // PC A bootstraps: Mac OBS is NOT active_on_other_agent for its own owner
        $pcABootstrap = $this->withHeaders([
            'Authorization' => 'Bearer usr_dual_obs_relay_token_456',
            'X-Obs-Agent-Instance' => 'macbook-pc-111',
        ])->getJson('/api/obs-agent/bootstrap')->assertOk();

        $studiosForA = collect($pcABootstrap->json('studios'))->keyBy('studio.id');
        $this->assertFalse((bool) $studiosForA[$macStudio->id]['active_on_other_agent']);

        // PC B cannot hijack Mac OBS in relay mode while PC A holds the online lease
        $this->withHeaders([
            'Authorization' => 'Bearer usr_dual_obs_relay_token_456',
            'X-Obs-Agent-Instance' => 'office-pc-222',
        ])->postJson('/api/obs-agent/state', [
            'studio_id' => $macStudio->id,
            'status' => 'online',
            'live_state' => ['currentProgramScene' => 'Accidental Office Hijack'],
        ])->assertOk()
            ->assertJsonPath('active_on_other_agent', true)
            ->assertJsonPath('ignored_duplicate_relay', true);

        $this->assertSame('Mac Scene', $macStudio->fresh()->getCurrentProgramScene());

        // PC B heartbeat for Mac OBS is also told to yield instead of stealing lease
        $this->withHeaders([
            'Authorization' => 'Bearer usr_dual_obs_relay_token_456',
            'X-Obs-Agent-Instance' => 'office-pc-222',
        ])->postJson('/api/obs-agent/heartbeat', [
            'studio_id' => $macStudio->id,
            'status' => 'online',
        ])->assertOk()
            ->assertJsonPath('active_on_other_agent', true)
            ->assertJsonPath('ignored_duplicate_relay', true);

        // PC B cleanly claims Office OBS
        $this->withHeaders([
            'Authorization' => 'Bearer usr_dual_obs_relay_token_456',
            'X-Obs-Agent-Instance' => 'office-pc-222',
        ])->postJson('/api/obs-agent/state', [
            'studio_id' => $officeStudio->id,
            'status' => 'online',
            'live_state' => ['currentProgramScene' => 'Office Stage'],
        ])->assertOk()->assertJsonPath('status', 'online');

        $this->assertSame('Office Stage', $officeStudio->fresh()->getCurrentProgramScene());

        // A dedicated studio-scoped agent token explicitly bound to Mac OBS can take over cleanly
        $this->withHeaders([
            'Authorization' => "Bearer {$macStudio->agent_token}",
            'X-Obs-Agent-Instance' => 'dedicated-mac-333',
        ])->postJson('/api/obs-agent/state', [
            'status' => 'online',
            'live_state' => ['currentProgramScene' => 'Dedicated Takeover'],
        ])->assertOk()->assertJsonPath('status', 'online');

        $this->assertSame('Dedicated Takeover', $macStudio->fresh()->getCurrentProgramScene());
    }

    public function test_control_center_defaults_installer_to_studio_scoped_when_account_has_multiple_studios(): void
    {
        ReverbStatusService::fakeUsing(static fn (): bool => true);

        $user = User::factory()->create();
        $firstStudio = ObsStudio::factory()->for($user)->create(['name' => 'Mac OBS']);

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $firstStudio->slug]))
            ->assertOk()
            ->assertSee('const defaultStudioScopedInstaller = false;', false);

        $secondStudio = ObsStudio::factory()->for($user)->create(['name' => 'Office OBS']);

        $this->actingAs($user)
            ->get(route('studio.show', ['studio' => $secondStudio->slug]))
            ->assertOk()
            ->assertSee('const defaultStudioScopedInstaller = true;', false)
            ->assertSee('?studio='.urlencode($secondStudio->slug), false);
    }
}
