<div class="grid md:grid-cols-2 gap-4">
    <input name="institution" placeholder="Institucion" value="{{ old('institution', $education->institution ?? '') }}" class="rounded border-gray-300" required>
    <input name="location" placeholder="Ciudad, estado o pais" value="{{ old('location', $education->location ?? '') }}" class="rounded border-gray-300">
    <input name="degree" placeholder="Titulo o grado" value="{{ old('degree', $education->degree ?? '') }}" class="rounded border-gray-300" required>
    <input name="field" placeholder="Area" value="{{ old('field', $education->field ?? '') }}" class="rounded border-gray-300">
    <input name="gpa" placeholder="GPA / promedio" value="{{ old('gpa', $education->gpa ?? '') }}" class="rounded border-gray-300">
    <input name="honors" placeholder="Honores o reconocimientos" value="{{ old('honors', $education->honors ?? '') }}" class="rounded border-gray-300">
    <input type="number" name="sort_order" placeholder="Orden" value="{{ old('sort_order', $education->sort_order ?? 0) }}" class="rounded border-gray-300">
    <input type="date" name="start_date" value="{{ old('start_date', optional($education?->start_date ?? null)->format('Y-m-d')) }}" class="rounded border-gray-300">
    <input type="date" name="end_date" value="{{ old('end_date', optional($education?->end_date ?? null)->format('Y-m-d')) }}" class="rounded border-gray-300">
    <textarea name="thesis" rows="3" placeholder="Tesis" class="rounded border-gray-300 md:col-span-2">{{ old('thesis', $education->thesis ?? '') }}</textarea>
    <textarea name="relevant_coursework" rows="3" placeholder="Cursos relevantes" class="rounded border-gray-300 md:col-span-2">{{ old('relevant_coursework', $education->relevant_coursework ?? '') }}</textarea>
    <textarea name="description" rows="4" placeholder="Notas" class="rounded border-gray-300 md:col-span-2">{{ old('description', $education->description ?? '') }}</textarea>
</div>
@if ($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
<button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
