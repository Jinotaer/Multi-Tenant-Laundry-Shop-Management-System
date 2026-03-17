import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    safelist: [
        // Theme preset classes — dynamically applied via ThemeService
        {
            pattern: /bg-(indigo|blue|emerald|purple|rose|slate)-(50|100|200|600|700|800)/,
            variants: ['hover'],
        },
        {
            pattern: /text-(indigo|blue|emerald|purple|rose|slate)-(600|700|800)/,
        },
        {
            pattern: /ring-(indigo|blue|emerald|purple|rose|slate)-500/,
            variants: ['focus'],
        },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
