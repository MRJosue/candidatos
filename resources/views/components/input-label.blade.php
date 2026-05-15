@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-orange-100']) }}>
    {{ $value ?? $slot }}
</label>
