<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed a predictable demo admin user for local and testing environments.
     */
    public function run(): void
    {
        Role::findOrCreate('admin');

        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Usuario Demo',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $user->syncRoles(['admin']);
    }
}
