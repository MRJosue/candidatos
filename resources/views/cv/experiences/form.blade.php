<div class="grid md:grid-cols-2 gap-4">
    <input name="position" placeholder="Puesto" value="{{ old('position', $experience->position ?? '') }}" class="rounded border-gray-300" required>
    <input name="company" placeholder="Empresa" value="{{ old('company', $experience->company ?? '') }}" class="rounded border-gray-300" required>
    <input name="location" placeholder="Ubicacion" value="{{ old('location', $experience->location ?? '') }}" class="rounded border-gray-300">
    <input type="number" name="sort_order" placeholder="Orden" value="{{ old('sort_order', $experience->sort_order ?? 0) }}" class="rounded border-gray-300">
    <input type="date" name="start_date" value="{{ old('start_date', optional($experience?->start_date ?? null)->format('Y-m-d')) }}" class="rounded border-gray-300" required>
    <input type="date" name="end_date" value="{{ old('end_date', optional($experience?->end_date ?? null)->format('Y-m-d')) }}" class="rounded border-gray-300">
    <label class="flex items-center gap-2 md:col-span-2"><input type="checkbox" name="is_current" value="1" @checked(old('is_current', $experience->is_current ?? false))> Trabajo actual</label>
    <textarea name="description" rows="5" placeholder="Logros y responsabilidades" class="rounded border-gray-300 md:col-span-2">{{ old('description', $experience->description ?? '') }}</textarea>
</div>
@if ($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
<button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
