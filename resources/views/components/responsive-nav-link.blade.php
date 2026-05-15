@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-amber-500 text-start text-base font-medium text-amber-800 bg-amber-50 focus:outline-none focus:text-amber-900 focus:bg-amber-100 focus:border-amber-700 transition duration-150 ease-in-out dark:bg-amber-950/40 dark:text-amber-100 dark:focus:bg-amber-950 dark:focus:text-white'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-amber-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-amber-300 transition duration-150 ease-in-out dark:text-orange-100 dark:hover:border-amber-700 dark:hover:bg-stone-800 dark:hover:text-white dark:focus:border-amber-700 dark:focus:bg-stone-800 dark:focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
