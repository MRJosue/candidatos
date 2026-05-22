@csrf

<div class="grid md:grid-cols-2 gap-4">
    <label class="block">
        <span class="text-sm text-gray-700">Nombre</span>
        <input name="name" value="{{ old('name', $company->name) }}" class="mt-1 w-full rounded border-gray-300" required>
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Industria</span>
        <input name="industry" value="{{ old('industry', $company->industry) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Email</span>
        <input type="email" name="email" value="{{ old('email', $company->email) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Sitio web</span>
        <input type="url" name="website_url" value="{{ old('website_url', $company->website_url) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block">
        <span class="text-sm text-gray-700">Ubicacion</span>
        <input name="location" value="{{ old('location', $company->location) }}" class="mt-1 w-full rounded border-gray-300">
    </label>
    <label class="block md:col-span-2">
        <span class="text-sm text-gray-700">Notas</span>
        <textarea name="notes" rows="5" class="mt-1 w-full rounded border-gray-300">{{ old('notes', $company->notes) }}</textarea>
    </label>
</div>

@if ($errors->any())
    <div class="text-sm text-red-700">{{ $errors->first() }}</div>
@endif

<div class="flex items-center gap-3">
    <button class="px-4 py-2 bg-gray-900 text-white rounded">Guardar</button>
    <a href="{{ route('companies.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded">Cancelar</a>
</div>
