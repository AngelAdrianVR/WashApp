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
                // Paleta "faded" de WashApp: tonos apagados y sofisticados
                // Gris pizarra suave: base de textos y superficies
                mist: {
                    50: '#f7f9fb',
                    100: '#eef2f6',
                    200: '#dfe7ee',
                    300: '#c7d5e0',
                    400: '#a3b8c8',
                    500: '#8098ac',
                    600: '#667d90',
                    700: '#536575',
                    800: '#45515d',
                    900: '#3a444e',
                },
                // Verde ecológico del logo (#50AD54), un poco más vivo
                sage: {
                    50: '#f1faf2',
                    100: '#dcf3de',
                    200: '#bbe7bf',
                    300: '#8dd494',
                    400: '#5cc066',
                    500: '#3fae45',
                    600: '#2f8f37',
                    700: '#287230',
                    800: '#245b2a',
                    900: '#1f4a25',
                },
                // Azul del logo (#0B6B9B), un poco más vivo
                cloud: {
                    50: '#eef7fc',
                    100: '#d9edf8',
                    200: '#b3dcf1',
                    300: '#7fc3e5',
                    400: '#45a4d3',
                    500: '#1585bd',
                    600: '#0b6b9b',
                    700: '#0a587f',
                    800: '#0d4a68',
                    900: '#113d55',
                },
                // Neutro cálido para contrastes suaves
                sand: {
                    50: '#faf8f5',
                    100: '#f3efe8',
                    200: '#e6ded1',
                    300: '#d5c8b4',
                    400: '#bfae95',
                    500: '#a99579',
                    600: '#8c7a62',
                },
            },
            boxShadow: {
                soft: '0 12px 40px -18px rgba(58, 68, 78, 0.28)',
                lift: '0 26px 60px -28px rgba(58, 68, 78, 0.45)',
            },
            keyframes: {
                floatSlow: {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-14px)' },
                },
                fadeUp: {
                    '0%': { opacity: '0', transform: 'translateY(18px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                floatSlow: 'floatSlow 9s ease-in-out infinite',
                fadeUp: 'fadeUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) both',
            },
        },
    },

    plugins: [forms, typography],
};
