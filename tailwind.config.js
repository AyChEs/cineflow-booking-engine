import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            // Archivo para la interfaz; Big Shoulders Display (cartelería) para títulos y marca (docs/MARCA.md).
            fontFamily: {
                sans: ['"Archivo Variable"', ...defaultTheme.fontFamily.sans],
                display: ['"Big Shoulders Display"', '"Archivo Variable"', ...defaultTheme.fontFamily.sans],
                contorno: ['"Big Shoulders Contorno"', '"Big Shoulders Display"', ...defaultTheme.fontFamily.sans],
            },
            // Tokens de plan/DISENO.md: sala oscura, carmín para actuar, marquesina solo para «lo tuyo».
            colors: {
                sala: { 950: '#0E0D10', 900: '#18161C', 850: '#221F27', 700: '#3A3641' },
                texto: { DEFAULT: '#F2EEE6', 2: '#A8A3AE' },
                // 500 es el rojo telón de la marca (docs/MARCA.md): símbolo, botón principal y grandes rótulos.
                carmin: { 400: '#F0566B', 500: '#E0284A', 600: '#B3203A', 700: '#95182F' },
                marquesina: '#EBD67C',
                foco: '#F5E6A8',
                ocupada: '#57525F',
                libre: '#8C8794',
                ok: '#6FD39A',
                error: '#FF7A85',
            },
            // Escala de espaciado vertical (sesión 13): una sola para todas las páginas públicas.
            //   bloque   entre bloques de una misma sección (20 a 32 px)
            //   pagina   relleno vertical de una página (24 a 40 px)
            //   seccion  entre secciones, antes del pie (40 a 64 px)
            spacing: {
                bloque: 'var(--esp-bloque)',
                pagina: 'var(--esp-pagina)',
                seccion: 'var(--esp-seccion)',
            },
            borderRadius: {
                control: '8px',
                card: '12px',
            },
        },
    },

    plugins: [forms],
};
