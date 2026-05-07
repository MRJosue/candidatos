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
        CvTemplate::updateOrCreate(
            ['slug' => 'clasico-profesional'],
            [
                'name' => 'Clasico profesional',
                'description' => 'Plantilla limpia para perfiles corporativos y administrativos.',
                'is_premium' => false,
                'price_cents' => 0,
                'currency' => 'MXN',
                'is_active' => true,
            ]
        );

        CvTemplate::updateOrCreate(
            ['slug' => 'ejecutivo-premium'],
            [
                'name' => 'Ejecutivo premium',
                'description' => 'Plantilla premium para perfiles senior, liderazgo y consultoria.',
                'is_premium' => true,
                'price_cents' => 29900,
                'currency' => 'MXN',
                'is_active' => true,
            ]
        );

        CvTemplate::updateOrCreate(
            ['slug' => 'academico-bullet'],
            [
                'name' => 'Academico bullet',
                'description' => 'Formato academico de una columna con secciones claras y bullets por logro.',
                'is_premium' => false,
                'price_cents' => 0,
                'currency' => 'MXN',
                'is_active' => true,
            ]
        );

        CvTemplate::updateOrCreate(
            ['slug' => 'creativo-sidebar'],
            [
                'name' => 'Creativo con barra lateral',
                'description' => 'Formato visual con barra lateral para contacto, premios, habilidades e intereses, sin iconos.',
                'is_premium' => true,
                'price_cents' => 29900,
                'currency' => 'MXN',
                'is_active' => true,
            ]
        );

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
