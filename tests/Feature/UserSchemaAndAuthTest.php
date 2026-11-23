<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class UserSchemaAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_without_email_and_optional_profile_fields(): void
    {
        $user = User::query()->create([
            'name' => 'Minimal User',
            'username' => 'minimal_user',
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'minimal_user',
            'email' => null,
            'contact_number' => null,
            'current_address' => null,
        ]);
    }

    public function test_username_is_required_and_unique(): void
    {
        User::query()->create([
            'name' => 'First User',
            'username' => 'unique_user',
            'password' => 'password',
        ]);

        $this->expectException(QueryException::class);

        User::query()->create([
            'name' => 'Second User',
            'username' => 'unique_user',
            'password' => 'password',
        ]);
    }

    public function test_contact_number_allows_multiple_nulls_and_enforces_uniqueness_when_present(): void
    {
        User::query()->create([
            'name' => 'Null Contact One',
            'username' => 'null_one',
            'contact_number' => null,
            'password' => 'password',
        ]);

        User::query()->create([
            'name' => 'Null Contact Two',
            'username' => 'null_two',
            'contact_number' => null,
            'password' => 'password',
        ]);

        User::query()->create([
            'name' => 'Contact Owner',
            'username' => 'contact_owner',
            'contact_number' => '9817208300',
            'current_address' => 'Kathmandu, Nepal',
            'password' => 'password',
        ]);

        $this->expectException(QueryException::class);

        User::query()->create([
            'name' => 'Duplicate Contact',
            'username' => 'dup_contact',
            'contact_number' => '9817208300',
            'password' => 'password',
        ]);
    }

    public function test_make_filament_user_command_uses_username_without_email_option(): void
    {
        $commands = Artisan::all();
        $command = $commands['make:filament-user'];
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('username'));
        $this->assertTrue($definition->hasOption('name'));
        $this->assertTrue($definition->hasOption('password'));
        $this->assertFalse($definition->hasOption('email'));

        $exitCode = Artisan::call('filament:make-user', [
            '--name' => 'CLI Admin',
            '--username' => 'cli_admin',
            '--password' => 'secret1234',
            '--panel' => 'admin',
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('users', [
            'name' => 'CLI Admin',
            'username' => 'cli_admin',
            'email' => null,
        ]);
    }

    public function test_filament_admin_login_authenticates_using_username(): void
    {
        $admin = User::query()->create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'contact_number' => '9817208300',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'admin',
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_filament_admin_login_rejects_invalid_credentials_on_username(): void
    {
        User::query()->create([
            'name' => 'Super Admin',
            'username' => 'admin',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'username' => 'admin',
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertGuest();
    }
}
