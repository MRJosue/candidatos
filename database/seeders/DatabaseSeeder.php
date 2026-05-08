<?php

namespace Database\Seeders;

use App\Models\CvTemplate;
use App\Models\Service;
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
