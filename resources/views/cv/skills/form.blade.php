<div class="grid md:grid-cols-2 gap-4">
    <input name="name" placeholder="Nombre" value="{{ old('name', $skill->name ?? '') }}" class="rounded border-gray-300" required>
    <select name="type" class="rounded border-gray-300" required>
        @php($selectedType = old('type', $skill->type ?? request('type', 'skill')))
        <option value="skill" @selected($selectedType === 'skill')>Habilidad</option>
        <option value="language" @selected($selectedType === 'language')>Idioma</option>
        <option value="soft_skill" @selected($selectedType === 'soft_skill')>Habilidad blanda</option>
    </select>
    <input name="category" placeholder="Categoria" value="{{ old('category', $skill->category ?? '') }}" class="rounded border-gray-300">
    <input type="number" min="1" max="5" name="level" placeholder="Nivel 1-5" value="{{ old('level', $skill->level ?? '') }}" class="rounded border-gray-300">
    <input type="number" name="sort_order" placeholder="Orden" value="{{ old('sort_order', $skill->sort_order ?? 0) }}" class="rounded border-gray-300">
</div>
@if ($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
<button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
