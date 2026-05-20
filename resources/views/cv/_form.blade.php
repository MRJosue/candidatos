@csrf
@if (filled(old('talent_id', $profile->talent_id ?? null)))
    <input type="hidden" name="talent_id" value="{{ old('talent_id', $profile->talent_id ?? null) }}">
@endif
@php
    $selectedTemplateId = old(
        'cv_template_id',
        $profile->cv_template_id ?: $templates->firstWhere('slug', 'act-digital')?->id
    );
@endphp
<div class="grid md:grid-cols-2 gap-4">
    <label class="block">
        <span class="text-sm text-gray-700">Titulo interno</span>
        <input name="title" value="{{ old('title', $profile->title ?? '') }}" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Plantilla</span>
        <select name="cv_template_id" class="mt-1 w-full rounded border-gray-300">
            @foreach ($templates as $template)
                <option value="{{ $template->id }}" @selected($selectedTemplateId == $template->id)>
                    {{ $template->name }}{{ $template->is_premium ? ' - Premium' : '' }}
                </option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Nombre completo</span>
        <input name="full_name" value="{{ old('full_name', $profile->full_name ?? auth()->user()->name) }}" class="mt-1 w-full rounded border-gray-300" required>
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
        <span class="text-sm text-gray-700">Headline</span>
        <input name="headline" value="{{ old('headline', $profile->headline ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Lema o frase breve</span>
        <input name="tagline" value="{{ old('tagline', $profile->tagline ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Resumen profesional</span>
        <textarea name="summary" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('summary', $profile->summary ?? '') }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Objetivo profesional</span>
        <textarea name="objective" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('objective', $profile->objective ?? '') }}</textarea>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Titulo de habilidades</span>
        <input name="skills_section_title" value="{{ old('skills_section_title', $profile->skills_section_title ?? 'Habilidades') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Titulo de habilidades blandas</span>
        <input name="soft_skills_section_title" value="{{ old('soft_skills_section_title', $profile->soft_skills_section_title ?? 'Habilidades blandas') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Premios y reconocimientos</span>
        <textarea name="awards" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('awards', $profile->awards ?? '') }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Liderazgo y actividades</span>
        <textarea name="leadership_activities" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('leadership_activities', $profile->leadership_activities ?? '') }}</textarea>
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Intereses</span>
        <textarea name="interests" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('interests', $profile->interests ?? '') }}</textarea>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">LinkedIn</span>
        <input name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Portafolio</span>
        <input name="portfolio_url" value="{{ old('portfolio_url', $profile->portfolio_url ?? '') }}" class="mt-1 w-full rounded border-gray-300">
    </label>
</div>
@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif
<button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
