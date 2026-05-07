@csrf

<div class="grid md:grid-cols-2 gap-4">
    <label class="block">
        <span class="text-sm text-gray-700">Postulante</span>
        <select name="talent_id" class="mt-1 w-full rounded border-gray-300" required>
            <option value="">Selecciona un postulante</option>
            @foreach ($talents as $talent)
                <option value="{{ $talent->id }}" @selected((string) old('talent_id', $application->talent_id) === (string) $talent->id)>{{ $talent->full_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Vacante</span>
        <select name="vacancy_id" class="mt-1 w-full rounded border-gray-300" required>
            <option value="">Selecciona una vacante</option>
            @foreach ($vacancies as $vacancy)
                <option value="{{ $vacancy->id }}" @selected((string) old('vacancy_id', $application->vacancy_id) === (string) $vacancy->id)>
                    {{ $vacancy->display_title }} - {{ $vacancy->display_company ?? 'Cliente confidencial' }}
                </option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">CV asociado</span>
        <select name="cv_profile_id" class="mt-1 w-full rounded border-gray-300">
            <option value="">Sin CV asociado</option>
            @foreach ($cvProfiles as $cvProfile)
                <option value="{{ $cvProfile->id }}" @selected((string) old('cv_profile_id', $application->cv_profile_id) === (string) $cvProfile->id)>{{ $cvProfile->title }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Score de match</span>
        <input type="number" min="0" max="100" name="match_score" value="{{ old('match_score', $application->match_score) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Estado</span>
        <select name="status" class="mt-1 w-full rounded border-gray-300" required>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $application->status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Etapa</span>
        <select name="stage" class="mt-1 w-full rounded border-gray-300" required>
            @foreach ($stageOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('stage', $application->stage) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Fecha de postulacion</span>
        <input type="datetime-local" name="applied_at" value="{{ old('applied_at', $application->applied_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Ultima actividad</span>
        <input type="datetime-local" name="last_activity_at" value="{{ old('last_activity_at', $application->last_activity_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Notas</span>
        <textarea name="notes" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $application->notes) }}</textarea>
    </label>
</div>

@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="flex items-center gap-3">
    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
    <a href="{{ route('applications.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Cancelar</a>
</div>
