@csrf

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
        <span class="text-sm text-gray-700">Email</span>
        <input type="email" name="email" value="{{ old('email', $talent->email) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Telefono</span>
        <input name="phone" value="{{ old('phone', $talent->phone) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Ubicacion</span>
        <input name="location" value="{{ old('location', $talent->location) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Estado</span>
        <select name="status" class="mt-1 w-full rounded border-gray-300" required>
            @foreach (['active' => 'Activo', 'inactive' => 'Inactivo', 'hired' => 'Contratado', 'rejected' => 'Descartado', 'paused' => 'Pausado'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $talent->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Headline</span>
        <input name="headline" value="{{ old('headline', $talent->headline) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Puesto objetivo</span>
        <input name="target_position" value="{{ old('target_position', $talent->target_position) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Senioridad</span>
        <input name="seniority" value="{{ old('seniority', $talent->seniority) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Fuente</span>
        <input name="source" value="{{ old('source', $talent->source) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Disponibilidad</span>
        <input name="availability" value="{{ old('availability', $talent->availability) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Expectativa minima</span>
        <input type="number" min="0" name="salary_expectation_min" value="{{ old('salary_expectation_min', $talent->salary_expectation_min) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Expectativa maxima</span>
        <input type="number" min="0" name="salary_expectation_max" value="{{ old('salary_expectation_max', $talent->salary_expectation_max) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Moneda</span>
        <input name="currency" value="{{ old('currency', $talent->currency ?? 'MXN') }}" maxlength="3" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Ultimo contacto</span>
        <input type="datetime-local" name="last_contacted_at" value="{{ old('last_contacted_at', $talent->last_contacted_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Stack tecnico</span>
        <textarea name="technical_stack" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('technical_stack', is_array($talent->technical_stack) ? implode(', ', $talent->technical_stack) : '') }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Idiomas</span>
        <textarea name="languages" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('languages', is_array($talent->languages) ? implode(', ', $talent->languages) : '') }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Links</span>
        <textarea name="links" rows="2" class="mt-1 w-full rounded border-gray-300">{{ old('links', is_array($talent->links) ? implode("\n", $talent->links) : '') }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Resumen tecnico</span>
        <textarea name="technical_summary" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('technical_summary', $talent->technical_summary) }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Notas internas</span>
        <textarea name="notes" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $talent->notes) }}</textarea>
    </label>
</div>

@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="flex items-center gap-3">
    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
    <a href="{{ route('talents.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Cancelar</a>
</div>
