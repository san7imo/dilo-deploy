import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'dilo-orange': '#ffa236',
                'dilo-orange-light': '#ffb54d',
                'dilo-black': '#000000',
                'dilo-gray-dark': '#1d1d1b',
                'dilo-gray': '#2a2a2a',
                'dilo-gray-light': '#2c2c2c',
            },
        },
    },

    plugins: [forms, typography],
};