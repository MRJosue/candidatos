@php
    $selectedTemplateId = old(
        'cv_template_id',
        $profile->cv_template_id ?: $templates->firstWhere('slug', 'act-digital')?->id
    );
@endphp

<details class="bg-white p-6 rounded shadow-sm">
    <summary class="cursor-pointer text-lg font-semibold text-gray-900">Otros apartados</summary>

    <div class="mt-5 grid md:grid-cols-2 gap-4">
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
            <span class="text-sm text-gray-700">Lema o frase breve</span>
            <input name="tagline" value="{{ old('tagline', $profile->tagline ?? '') }}" class="mt-1 w-full rounded border-gray-300">
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700">Objetivo profesional</span>
            <textarea name="objective" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('objective', $profile->objective ?? '') }}</textarea>
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
</details>
