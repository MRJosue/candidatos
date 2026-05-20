# CVStudio

## Comandos exactos usados

```bash
composer create-project laravel/laravel cv-studio
cd cv-studio
composer require laravel/breeze spatie/laravel-permission laravel/cashier barryvdh/laravel-dompdf
php artisan breeze:install blade
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --tag="cashier-migrations"
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
pnpm install --frozen-lockfile
pnpm run build
php artisan migrate --seed
php artisan serve
```

## Configuracion inicial

`.env` queda preparado para:

```dotenv
APP_NAME=CVStudio
APP_URL=http://localhost:8000
APP_LOCALE=es
APP_TIMEZONE=America/Mexico_City
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cvstudio
DB_USERNAME=root
DB_PASSWORD=
STRIPE_KEY=
STRIPE_SECRET=
```

Antes de migrar, crea la base de datos MySQL:

```sql
CREATE DATABASE cvstudio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Estructura principal

```text
app/
  Http/Controllers/
    DashboardController.php
    CvProfileController.php
    CvExperienceController.php
    CvEducationController.php
    CvSkillController.php
    CvTemplateController.php
    PurchaseController.php
    AppointmentController.php
  Http/Middleware/EnsurePremiumTemplateAccess.php
  Http/Requests/
  Models/
  Policies/CvProfilePolicy.php
database/
  migrations/
  seeders/DatabaseSeeder.php
resources/views/
  cv/
  templates/
  purchases/
  appointments/
routes/web.php
```

## Modulos incluidos

- Auth Blade con Laravel Breeze.
- Roles y permisos con Spatie Permission.
- Cashier instalado para Stripe; el controlador de compras registra compras y deja el punto de extension para checkout real con `stripe_price_id`.
- DomPDF para exportar CVs desde Blade.
- CRUD base de CV, experiencia, educacion y habilidades.
- Plantillas basicas/premium y seleccion por CV.
- Agenda de consultoria RH con servicios sembrados.
