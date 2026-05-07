<div class="grid md:grid-cols-2 gap-4">
    <select name="service_id" class="rounded border-gray-300" required>
        @foreach ($services as $service)
            <option value="{{ $service->id }}" @selected(old('service_id', $appointment->service_id ?? '') == $service->id)>
                {{ $service->name }} · {{ $service->duration_minutes }} min
            </option>
        @endforeach
    </select>
    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($appointment?->scheduled_at ?? null)->format('Y-m-d\TH:i')) }}" class="rounded border-gray-300" required>
    <input name="timezone" value="{{ old('timezone', $appointment->timezone ?? config('app.timezone')) }}" class="rounded border-gray-300">
    <textarea name="notes" rows="4" placeholder="Notas" class="rounded border-gray-300 md:col-span-2">{{ old('notes', $appointment->notes ?? '') }}</textarea>
</div>
@if ($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
<button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
