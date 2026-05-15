<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-amber-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-800 focus:bg-amber-800 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 dark:bg-amber-600 dark:hover:bg-amber-500 dark:focus:bg-amber-500 dark:active:bg-amber-700 dark:focus:ring-offset-stone-950']) }}>
    {{ $slot }}
</button>
