import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Cinema CineFlow palette — mirror de las CSS variables del tema
                'cinema': {
                    'bg':        '#121212',
                    'surface':   '#1a1a1a',
                    'red':       '#800000',
                    'accent':    '#d4183d',
                    'muted':     '#9ca3af',
                    'border':    'rgba(255,255,255,0.1)',
                    'shadow':    'rgba(128,0,0,0.5)',
                },
            },
            backgroundOpacity: {
                '8': '0.08',
            },
        },
    },

    plugins: [forms],
};
