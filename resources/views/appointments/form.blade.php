<div class="grid md:grid-cols-2 gap-4">
    <label class="block">
        <span class="text-sm text-gray-700">Candidato</span>
        <select name="talent_id" class="mt-1 w-full rounded border-gray-300" required @disabled($talents->isEmpty())>
            <option value="">Selecciona un candidato</option>
            @foreach ($talents as $talent)
                <option value="{{ $talent->id }}" @selected((string) old('talent_id', $appointment->talent_id ?? '') === (string) $talent->id)>
                    {{ $talent->full_name }}
                </option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Vacante</span>
        <select name="vacancy_id" class="mt-1 w-full rounded border-gray-300" required @disabled($vacancies->isEmpty())>
            <option value="">Selecciona una vacante</option>
            @foreach ($vacancies as $vacancy)
                <option value="{{ $vacancy->id }}" @selected((string) old('vacancy_id', $appointment->vacancy_id ?? '') === (string) $vacancy->id)>
                    {{ $vacancy->display_title }} - {{ $vacancy->display_company ?? 'Cliente confidencial' }}
                </option>
            @endforeach
        </select>
    </label>
    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($appointment?->scheduled_at ?? null)->format('Y-m-d\TH:i')) }}" class="rounded border-gray-300" required>
    <input name="timezone" value="{{ old('timezone', $appointment->timezone ?? config('app.timezone')) }}" class="rounded border-gray-300">
    <textarea name="notes" rows="4" placeholder="Notas" class="rounded border-gray-300 md:col-span-2">{{ old('notes', $appointment->notes ?? '') }}</textarea>
</div>
@if ($talents->isEmpty() || $vacancies->isEmpty())
    <p class="text-sm text-amber-700">Necesitas al menos un candidato y una vacante para agendar una cita.</p>
@endif
@if ($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
<button class="px-4 py-2 bg-gray-900 text-white rounded disabled:opacity-50" @disabled($talents->isEmpty() || $vacancies->isEmpty())>Guardar</button>
