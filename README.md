# CV Studio

Aplicacion Laravel para gestion de talento, vacantes, postulaciones y generacion de CVs.

## Stack

- Laravel Blade
- Vite + Tailwind CSS
- Alpine.js
- pnpm como gestor de paquetes frontend

## Desarrollo local

```bash
composer install
pnpm install --frozen-lockfile
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
pnpm run dev
php artisan serve
```

## Datos demo de reclutamiento

Para cargar talentos, companias, vacantes y postulaciones de prueba solo para usuarios con rol `admin`:

```bash
composer seed-demo-recruiting
```

El seeder es idempotente: puedes ejecutarlo mas de una vez y actualizara los mismos registros demo sin duplicarlos.

## Build de estilos

El proyecto usa solo `pnpm`. No uses `npm install` ni commits de `package-lock.json`.

```bash
pnpm install --frozen-lockfile
pnpm run build
```

El build de Vite queda en:

```text
public/build/manifest.json
public/build/assets/*
```

## Deploy en Hostinger

En este hosting el codigo vive en `app/`, pero el dominio sirve archivos publicos desde `public_html/`. Despues de `git pull`, sincroniza el build:

```bash
cd /home/u269904761/domains/candidatos.icu/app
git pull origin main
pnpm install --frozen-lockfile
pnpm run deploy:hostinger:assets
php artisan optimize:clear
```

Si `public_html` esta en otra ruta:

```bash
HOSTINGER_PUBLIC_HTML=/ruta/al/public_html pnpm run deploy:hostinger:assets
```

Para comprobar que los estilos estan publicados:

```bash
cat public/build/manifest.json
ls -la ../public_html/build/assets
```
