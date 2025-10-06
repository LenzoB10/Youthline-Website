import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    darkMode: 'class', // enable dark mode using class
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                mustBlue: '#10274A',
                mustGreen: '#55B166',
                background: {
                    DEFAULT: '#F9FAFB', // light
                    soft: '#f1f1f1',
                    card: '#ffffff',
                    dark: '#0f0f0f', // dark
                    'dark-soft': '#181818',
                    'dark-card': '#212121',
                },
                text: {
                    base: '#0f0f0f',
                    muted: '#606060',
                    inverted: '#F3F4F6',
                    'dark-muted': '#aaaaaa',
                },
                accent: {
                    DEFAULT: '#55B166', // green-500
                    light: '#86efac',
                    dark: '#15803d',
                },

                // Text Colors
                mustText: {
                    base: '#10274A', // Default text
                    muted: '#C7CDD3', // Gray text
                    light: '#E0E6ED', // Light text on dark
                },

                // Borders
                mustBorder: {
                    light: '#E5E7EB',
                    dark: '#334155',
                },
            },
        },
    },
    plugins: [forms],
};
