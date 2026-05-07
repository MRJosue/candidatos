<x-guest-layout>
    <div class="mb-5 text-center">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#3157d5]">Nuevo espacio de trabajo</p>
        <h2 class="mt-2 text-2xl font-bold tracking-normal text-slate-950">Crea tu cuenta</h2>
        <p class="mx-auto mt-2 max-w-xs text-sm leading-5 text-slate-500">
            Configura tu acceso para empezar a organizar candidatos, vacantes y curriculums.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre completo" class="text-sm font-semibold text-slate-700" />
            <x-text-input id="name" class="mt-1.5 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#3157d5] focus:ring-[#3157d5]" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Tu nombre" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" value="Correo electronico" class="text-sm font-semibold text-slate-700" />
            <x-text-input id="email" class="mt-1.5 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#3157d5] focus:ring-[#3157d5]" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="reclutador@empresa.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Contrasena" class="text-sm font-semibold text-slate-700" />

            <x-text-input id="password" class="mt-1.5 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#3157d5] focus:ring-[#3157d5]"
                type="password"
                name="password"
                required autocomplete="new-password"
                placeholder="Minimo 8 caracteres" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar contrasena" class="text-sm font-semibold text-slate-700" />

            <x-text-input id="password_confirmation" class="mt-1.5 block w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-[#3157d5] focus:ring-[#3157d5]"
                type="password"
                name="password_confirmation" required autocomplete="new-password"
                placeholder="Repite tu contrasena" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="flex w-full justify-center rounded-lg bg-[#3157d5] px-4 py-2.5 text-sm font-bold hover:bg-[#1f3f9f] focus:bg-[#1f3f9f] active:bg-[#172033]">
                Crear cuenta
            </x-primary-button>
        </div>

        <p class="mt-5 text-center text-sm text-slate-500">
            Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-semibold text-[#3157d5] hover:text-[#1f3f9f]">Inicia sesion</a>
        </p>
    </form>
</x-guest-layout>
