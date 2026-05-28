<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AccountHierarchyDemoSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $owner = User::updateOrCreate(
            ['email' => 'jefe.act@example.com'],
            [
                'name' => 'Jefe ACT',
                'account_owner_id' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        $owner->syncRoles(['jefe_cuenta']);

        collect([
            [
                'name' => 'Reclutador ACT Uno',
                'email' => 'reclutador1.act@example.com',
            ],
            [
                'name' => 'Reclutador ACT Dos',
                'email' => 'reclutador2.act@example.com',
            ],
        ])->each(function (array $data) use ($owner): void {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'account_owner_id' => $owner->id,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ],
            );

            $user->syncRoles(['usuario_subordinado']);
        });
    }
}
