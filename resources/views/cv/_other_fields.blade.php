@php
    $selectedTemplateId = old(
        'cv_template_id',
        $profile->cv_template_id ?: $templates->firstWhere('slug', 'act-digital')?->id
    );
@endphp

@php
    $otherFieldNames = [
        'cv_template_id',
        'tagline',
        'objective',
        'leadership_activities',
        'interests',
        'linkedin_url',
        'portfolio_url',
    ];
    $hasOtherFieldErrors = collect($otherFieldNames)->contains(fn ($field) => $errors->has($field));
@endphp

<details class="bg-white p-6 rounded shadow-sm" @if ($hasOtherFieldErrors) open @endif>
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
            <x-input-error class="mt-2" :messages="$errors->get('cv_template_id')" />
        </label>

        <label class="block">
            <span class="text-sm text-gray-700">Lema o frase breve</span>
            <input name="tagline" value="{{ old('tagline', $profile->tagline ?? '') }}" class="mt-1 w-full rounded border-gray-300">
            <x-input-error class="mt-2" :messages="$errors->get('tagline')" />
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700">Objetivo profesional</span>
            <textarea name="objective" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('objective', $profile->objective ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('objective')" />
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700">Liderazgo y actividades</span>
            <textarea name="leadership_activities" rows="4" class="mt-1 w-full rounded border-gray-300">{{ old('leadership_activities', $profile->leadership_activities ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('leadership_activities')" />
        </label>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700">Intereses</span>
            <textarea name="interests" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('interests', $profile->interests ?? '') }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('interests')" />
        </label>

        <label class="block">
            <span class="text-sm text-gray-700">LinkedIn</span>
            <input name="linkedin_url" value="{{ old('linkedin_url', $profile->linkedin_url ?? '') }}" class="mt-1 w-full rounded border-gray-300">
            <x-input-error class="mt-2" :messages="$errors->get('linkedin_url')" />
        </label>

        <label class="block">
            <span class="text-sm text-gray-700">Portafolio</span>
            <input name="portfolio_url" value="{{ old('portfolio_url', $profile->portfolio_url ?? '') }}" class="mt-1 w-full rounded border-gray-300">
            <x-input-error class="mt-2" :messages="$errors->get('portfolio_url')" />
        </label>
    </div>
</details>
