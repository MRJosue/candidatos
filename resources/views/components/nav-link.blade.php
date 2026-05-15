@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-amber-500 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-amber-700 transition duration-150 ease-in-out dark:text-orange-50 dark:focus:border-amber-400'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-amber-300 focus:outline-none focus:text-gray-700 focus:border-amber-300 transition duration-150 ease-in-out dark:text-orange-100 dark:hover:border-amber-700 dark:hover:text-white dark:focus:border-amber-700 dark:focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
