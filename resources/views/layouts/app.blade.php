<!DOCTYPE html>
@php
    $applicationTheme = Auth::user()?->applicationTheme ?? \App\Models\ApplicationTheme::default();
    $appearanceMode = Auth::user()?->theme_mode ?? 'system';
    $backgroundImageUrl = $applicationTheme->backgroundImageUrl();
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-app-theme="{{ $applicationTheme->slug }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script>
            window.appAppearance = {
                mode: @json($appearanceMode),
                theme: @json($applicationTheme->slug),
            };

            (() => {
                const preferredMode = window.appAppearance.mode ?? 'system';
                const theme = preferredMode === 'system' ? localStorage.getItem('theme') : preferredMode;
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const useDark = theme === 'dark' || (! theme && prefersDark);

                document.documentElement.classList.toggle('dark', useDark);
                document.documentElement.style.colorScheme = useDark ? 'dark' : 'light';
            })();
        </script>
        @wireUiScripts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root[data-app-theme="{{ $applicationTheme->slug }}"] {
                @foreach ($applicationTheme->paletteFor('light') as $token => $value)
                    --cv-{{ $token }}: {{ $value }};
                @endforeach
                --cv-bg-image: {{ $backgroundImageUrl ? "url('".$backgroundImageUrl."')" : 'none' }};
            }

            html.dark[data-app-theme="{{ $applicationTheme->slug }}"] {
                @foreach ($applicationTheme->paletteFor('dark') as $token => $value)
                    --cv-{{ $token }}: {{ $value }};
                @endforeach
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="app-shell min-h-screen bg-gray-100 transition-colors duration-200 dark:bg-stone-950">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow transition-colors duration-200 dark:bg-stone-900 dark:shadow-stone-950/40">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
