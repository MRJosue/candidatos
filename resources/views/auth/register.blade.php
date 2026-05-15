<x-guest-layout>
    <div class="mb-5 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--cv-accent)]">Nuevo espacio de trabajo</p>
        <h2 class="mt-2 text-2xl font-bold tracking-normal text-[var(--cv-text)]">Crea tu cuenta</h2>
        <p class="mx-auto mt-2 max-w-xs text-sm leading-5 text-[var(--cv-text-muted)]">
            Configura tu acceso para empezar a organizar candidatos, vacantes y curriculums.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre completo" class="text-sm font-semibold text-[var(--cv-text)]" />
            <x-text-input id="name" class="mt-1.5 block w-full rounded-lg border-[var(--cv-border)] bg-[var(--cv-surface)] px-3 py-2.5 text-sm text-[var(--cv-text)] shadow-sm focus:border-[var(--cv-accent)] focus:ring-[var(--cv-accent)]" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Tu nombre" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Correo electronico" class="text-sm font-semibold text-[var(--cv-text)]" />
            <x-text-input id="email" class="mt-1.5 block w-full rounded-lg border-[var(--cv-border)] bg-[var(--cv-surface)] px-3 py-2.5 text-sm text-[var(--cv-text)] shadow-sm focus:border-[var(--cv-accent)] focus:ring-[var(--cv-accent)]" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="reclutador@empresa.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contrasena" class="text-sm font-semibold text-[var(--cv-text)]" />

            <x-text-input id="password" class="mt-1.5 block w-full rounded-lg border-[var(--cv-border)] bg-[var(--cv-surface)] px-3 py-2.5 text-sm text-[var(--cv-text)] shadow-sm focus:border-[var(--cv-accent)] focus:ring-[var(--cv-accent)]"
                type="password"
                name="password"
                required autocomplete="new-password"
                placeholder="Minimo 8 caracteres" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contrasena" class="text-sm font-semibold text-[var(--cv-text)]" />

            <x-text-input id="password_confirmation" class="mt-1.5 block w-full rounded-lg border-[var(--cv-border)] bg-[var(--cv-surface)] px-3 py-2.5 text-sm text-[var(--cv-text)] shadow-sm focus:border-[var(--cv-accent)] focus:ring-[var(--cv-accent)]"
                type="password"
                name="password_confirmation" required autocomplete="new-password"
                placeholder="Repite tu contrasena" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="flex w-full justify-center rounded-lg bg-[var(--cv-accent)] px-4 py-2.5 text-sm font-bold hover:bg-[var(--cv-accent-hover)] focus:bg-[var(--cv-accent-hover)] active:bg-[var(--cv-text)]">
                Crear cuenta
            </x-primary-button>
        </div>

        <p class="mt-5 text-center text-sm text-[var(--cv-text-muted)]">
            Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-semibold text-[var(--cv-accent)] hover:text-[var(--cv-accent-hover)]">Inicia sesion</a>
        </p>
    </form>
</x-guest-layout>
