<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand as BaseMakeUserCommand;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeFilamentUserCommand extends BaseMakeUserCommand
{
    protected $description = 'Create a new Filament user using username, password, and name';

    protected $name = 'make:filament-user';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'filament:make-user',
        'filament:user',
    ];

    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'name',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The name of the user',
            ),
            new InputOption(
                name: 'username',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'A valid and unique username',
            ),
            new InputOption(
                name: 'password',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The password for the user (min. 8 characters)',
            ),
            new InputOption(
                name: 'panel',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The panel to create the user in',
            ),
        ];
    }

    /**
     * @return array{'name': string, 'username': string, 'password': string}
     */
    protected function getUserData(): array
    {
        $name = trim((string) ($this->options['name'] ?? text(
            label: 'Name',
            required: true,
        )));

        $username = trim((string) ($this->options['username'] ?? text(
            label: 'Username',
            required: true,
            validate: fn (string $username): ?string => match (true) {
                trim($username) === '' => 'The username is required.',
                static::getUserModel()::query()->where('username', trim($username))->exists() => 'A user with this username already exists.',
                default => null,
            },
        )));

        $plainPassword = $this->options['password'] ?? password(
            label: 'Password',
            required: true,
        );

        return [
            'name' => $name,
            'username' => $username,
            'password' => Hash::make($plainPassword),
        ];
    }

    protected function sendSuccessMessage(Model&Authenticatable $user): void
    {
        $loginUrl = $this->panel->getLoginUrl();
        $username = $user->getAttribute('username') ?? $user->getAttribute('name') ?? 'You';

        $this->components->info("Success! {$username} may now log in at {$loginUrl}");
    }
}
