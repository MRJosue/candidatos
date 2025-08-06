<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'ingjosue.cardona@gmail.com'],
            [
                'name' => 'Josue Cardona',
                'password' => bcrypt('admin123'),
            ]
        );

        // Si usas Spatie:
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('admin');
        }
    }
}