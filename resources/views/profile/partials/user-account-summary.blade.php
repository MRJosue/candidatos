<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Datos de usuario
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Informacion principal de tu cuenta en la plataforma.
        </p>
    </header>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->name }}</dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Correo</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->email }}</dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Roles</dt>
            <dd class="mt-1 text-sm text-gray-900">
                @forelse ($user->roles as $role)
                    <span class="me-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $role->name }}</span>
                @empty
                    <span class="text-gray-500">Sin rol asignado</span>
                @endforelse
            </dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Primer inicio</dt>
            <dd class="mt-1">
                @if ($user->first_login)
                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Pendiente</span>
                @else
                    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">Completado</span>
                @endif
            </dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Alta</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->created_at?->format('d/m/Y') }}</dd>
        </div>

        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ultima actualizacion</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->updated_at?->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>
</section>
