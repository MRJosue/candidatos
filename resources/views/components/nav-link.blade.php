@props(['active'])

@php
$classes = ($active ?? false)
            ? 'app-nav-link app-nav-link-active inline-flex h-full items-center px-3 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out'
            : 'app-nav-link inline-flex h-full items-center px-3 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
