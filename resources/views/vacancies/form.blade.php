@csrf

<div class="space-y-6">
    <section>
        <h3 class="font-semibold mb-4">Compania</h3>
        <label class="block">
            <span class="text-sm text-gray-700">Compania relacionada</span>
            <select name="company_id" class="mt-1 w-full rounded border-gray-300" required>
                <option value="">Selecciona una compania</option>
                @foreach ($companies as $catalogCompany)
                    <option value="{{ $catalogCompany->id }}" @selected((string) old('company_id', $vacancy->company_id) === (string) $catalogCompany->id)>
                        {{ $catalogCompany->name }}
                        @if ($catalogCompany->industry)
                            - {{ $catalogCompany->industry }}
                        @endif
                    </option>
                @endforeach
            </select>
        </label>

        @if ($companies->isEmpty())
            <p class="mt-2 text-sm text-amber-700">No hay companias registradas en el catalogo.</p>
        @endif
    </section>

    <section>
        <h3 class="font-semibold mb-4">Puesto</h3>
        <div class="grid md:grid-cols-2 gap-4">
            <label class="block">
                <span class="text-sm text-gray-700">Titulo del puesto</span>
                <input name="position_title" value="{{ old('position_title', $position->title) }}" class="mt-1 w-full rounded border-gray-300" required>
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Departamento</span>
                <input name="position_department" value="{{ old('position_department', $position->department) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Senioridad</span>
                <input name="seniority" value="{{ old('seniority', $position->seniority) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Tipo de empleo</span>
                <input name="employment_type" value="{{ old('employment_type', $position->employment_type) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Modalidad</span>
                <input name="work_mode" value="{{ old('work_mode', $position->work_mode) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Ubicacion del puesto</span>
                <input name="location" value="{{ old('location', $position->location) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Salario minimo</span>
                <input type="number" min="0" name="salary_min" value="{{ old('salary_min', $position->salary_min) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Salario maximo</span>
                <input type="number" min="0" name="salary_max" value="{{ old('salary_max', $position->salary_max) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Moneda</span>
                <input name="currency" value="{{ old('currency', $position->currency ?? 'MXN') }}" maxlength="3" class="mt-1 w-full rounded border-gray-300" required>
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Estado de vacante</span>
                <select name="status" class="mt-1 w-full rounded border-gray-300" required>
                    @foreach (['open' => 'Abierta', 'paused' => 'Pausada', 'closed' => 'Cerrada', 'filled' => 'Cubierta', 'cancelled' => 'Cancelada'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $vacancy->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Fecha de apertura</span>
                <input type="datetime-local" name="opened_at" value="{{ old('opened_at', $vacancy->opened_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block">
                <span class="text-sm text-gray-700">Fecha de cierre</span>
                <input type="datetime-local" name="closed_at" value="{{ old('closed_at', $vacancy->closed_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm text-gray-700">Stack tecnico requerido</span>
                <textarea name="technical_stack" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('technical_stack', is_array($position->technical_stack) ? implode(', ', $position->technical_stack) : '') }}</textarea>
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm text-gray-700">Descripcion</span>
                <textarea name="description" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('description', $position->description) }}</textarea>
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm text-gray-700">Requisitos</span>
                <textarea name="requirements" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('requirements', $position->requirements) }}</textarea>
            </label>
        </div>
    </section>
</div>

@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="flex items-center gap-3">
    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
    <a href="{{ route('vacancies.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Cancelar</a>
</div>
