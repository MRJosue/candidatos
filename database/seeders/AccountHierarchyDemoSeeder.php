<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class AccountHierarchyDemoSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const DEMO_EMAILS = [
        'jefe.act@example.com',
        'reclutador1.act@example.com',
        'reclutador2.act@example.com',
    ];

    public function run(): void
    {
        Role::findOrCreate('jefe_cuenta');
        Role::findOrCreate('usuario_subordinado');

        $this->resetDemoUsers();

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function resetDemoUsers(): void
    {
        DB::transaction(function (): void {
            $demoUsers = User::query()
                ->whereIn('email', self::DEMO_EMAILS)
                ->get(['id', 'email']);

            if ($demoUsers->isEmpty()) {
                return;
            }

            $userIds = $demoUsers->pluck('id');

            DB::table('sessions')
                ->whereIn('user_id', $userIds)
                ->delete();

            $subscriptionIds = DB::table('subscriptions')
                ->whereIn('user_id', $userIds)
                ->pluck('id');

            if ($subscriptionIds->isNotEmpty()) {
                DB::table('subscription_items')
                    ->whereIn('subscription_id', $subscriptionIds)
                    ->delete();
            }

            DB::table('subscriptions')
                ->whereIn('user_id', $userIds)
                ->delete();

            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $userIds)
                ->delete();

            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->whereIn('model_id', $userIds)
                ->delete();

            User::query()
                ->whereIn('id', $userIds)
                ->orderByRaw("CASE WHEN email = 'jefe.act@example.com' THEN 1 ELSE 0 END")
                ->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
