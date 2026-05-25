@csrf
@if (filled(old('talent_id', $profile->talent_id ?? null)))
    <input type="hidden" name="talent_id" value="{{ old('talent_id', $profile->talent_id ?? null) }}">
@endif
<div class="grid md:grid-cols-2 gap-4">
    <label class="block">
        <span class="text-sm text-gray-700">Titulo interno / nombre de archivo</span>
        <input name="title" value="{{ old('title', $profile->title ?? '') }}" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Idioma del CV</span>
        <select name="language" class="mt-1 w-full rounded border-gray-300">
            @foreach (($languageOptions ?? \App\Models\CvProfile::languageOptions()) as $language => $label)
                <option value="{{ $language }}" @selected(old('language', $profile->language ?? 'es') === $language)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Nombre completo</span>
        <input name="full_name" value="{{ old('full_name', $profile->full_name ?? auth()->user()->name) }}" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Headline</span>
        <input name="headline" value="{{ old('headline', $profile->headline ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Correo electrónico</span>
        <input type="email" name="email" value="{{ old('email', $profile->email ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Teléfono</span>
        <input name="phone" value="{{ old('phone', $profile->phone ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Ubicación</span>
        <input name="location" value="{{ old('location', $profile->location ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Resumen profesional</span>
        <textarea name="summary" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('summary', $profile->summary ?? '') }}</textarea>
    </label>
</div>
@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif
@if ($showSubmitButton ?? true)
    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
@endif
