<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'contact_number' => '9817208300',
                'email' => null,
                'current_address' => null,
            ],
        );
    }
}
