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
            colors: {
                brand: {
                    500: '#11624f',
                    600: '#0F3D32',
                },
                primary: {
                    500: '#11624f',
                    DEFAULT: '#0F3D32',
                    hover: '#0A2D25',
                },
                dark: {
                    900: '#0F3D32',
                    800: '#0A2D25',
                    700: '#11624f',
                    600: '#2E6B57',
                },
            },
            fontFamily: {
                sans: ['Roboto', ...defaultTheme.fontFamily.sans],
                heading: ['Oswald', 'sans-serif'],
            },
        },
    },

    plugins: [forms],
};
