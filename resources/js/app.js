import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const appearance = window.appAppearance ?? {};
const preferredMode = appearance.mode ?? 'system';
const storedTheme = preferredMode === 'system' ? localStorage.getItem('theme') : preferredMode;
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

const applyTheme = (theme) => {
    const isDark = theme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

Alpine.store('theme', {
    preference: preferredMode,
    value: storedTheme ?? (prefersDark ? 'dark' : 'light'),

    init() {
        applyTheme(this.value);
    },

    toggle() {
        this.value = this.value === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', this.value);
        this.preference = this.value;
        applyTheme(this.value);
    },

    isDark() {
        return this.value === 'dark';
    },
});

Alpine.start();
