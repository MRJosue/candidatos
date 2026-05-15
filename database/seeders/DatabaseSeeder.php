<?php

namespace Database\Seeders;

use App\Models\CvTemplate;
use App\Models\ApplicationTheme;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::findOrCreate('admin');
        Role::findOrCreate('cliente');

        User::where('email', 'ingjosue.cardona@gmail.com')
            ->first()
            ?->syncRoles(['admin']);

        User::where('email', '!=', 'ingjosue.cardona@gmail.com')
            ->get()
            ->each
            ->syncRoles(['cliente']);

        ApplicationTheme::ensureDefaultThemes();
        CvTemplate::ensureDefaultTemplates();

        Service::updateOrCreate(
            ['slug' => 'consultoria-rh-cv'],
            [
                'name' => 'Consultoria RH para CV',
                'description' => 'Sesion individual para revisar CV, narrativa profesional y estrategia de postulacion.',
                'price_cents' => 85000,
                'currency' => 'MXN',
                'duration_minutes' => 60,
                'is_active' => true,
            ]
        );
    }
}
