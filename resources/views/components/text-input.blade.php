@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm dark:border-stone-600 dark:bg-stone-900 dark:text-orange-50 dark:placeholder-stone-400']) }}>
