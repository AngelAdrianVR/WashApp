import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    // Enable dark mode using the 'class' strategy
    darkMode: 'class',

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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Custom color palette for the car wash theme
            colors: {
                'primary': {
                    DEFAULT: '#0052D4', // Un azul corporativo y moderno
                    light: '#4364F7',   // Un azul más brillante para hover y acentos
                },
                'secondary': {
                    DEFAULT: '#00C6A7', // Un turquesa que evoca limpieza y agua
                    light: '#59E4D0',   // Un tono más claro para interacciones
                },
                'tertiary': {
                    DEFAULT: '#FFD700', // Un amarillo dorado para llamadas a la acción
                    light: '#FFEA70',   // Un amarillo más suave para el hover
                },
            }
        },
    },

    plugins: [forms, typography],
};
