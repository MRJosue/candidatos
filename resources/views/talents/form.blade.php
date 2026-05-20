@csrf

<div class="space-y-5">
    <div>
        <h3 class="text-base font-semibold text-gray-900">Ficha minima del talento</h3>
        <p class="mt-1 text-sm text-gray-500">Captura solo los datos operativos. La informacion profesional, narrativa, links, habilidades e idiomas se administra desde el CV.</p>
    </div>

<div class="grid md:grid-cols-2 gap-4">
    <label class="block">
        <span class="text-sm text-gray-700">Nombre</span>
        <input name="first_name" value="{{ old('first_name', $talent->first_name) }}" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Apellido</span>
        <input name="last_name" value="{{ old('last_name', $talent->last_name) }}" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Estado</span>
        <select name="status" class="mt-1 w-full rounded border-gray-300" required>
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'hired' => 'Contratado', 'rejected' => 'Descartado', 'paused' => 'Pausado'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $talent->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Fuente</span>
        <input name="source" value="{{ old('source', $talent->source) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Ultimo contacto</span>
        <input type="datetime-local" name="last_contacted_at" value="{{ old('last_contacted_at', $talent->last_contacted_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Notas internas</span>
        <textarea name="notes" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $talent->notes) }}</textarea>
    </label>
</div>
</div>

@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="flex items-center gap-3">
    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
    <a href="{{ route('talents.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Cancelar</a>
</div>
