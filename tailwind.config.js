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

    theme: {
        extend: {
            fontFamily: {
                sans: ['Vazirmatn', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#5b6ee8',
                    600: '#2952e0',
                    700: '#2140b8',
                    800: '#1c3491',
                    900: '#122c7a',
                    950: '#0c1c4d',
                },
                amber: {
                    50: '#fff8ec',
                    100: '#ffefc9',
                    200: '#ffdb8e',
                    300: '#ffc152',
                    400: '#ffa22a',
                    500: '#ff8a1e',
                    600: '#e46c0f',
                    700: '#bd530f',
                    800: '#984014',
                    900: '#7c3614',
                    950: '#431a08',
                },
                ink: {
                    50: '#f5f6fa',
                    100: '#eaecf5',
                    200: '#d3d7e8',
                    300: '#aab1cf',
                    400: '#7b84ac',
                    500: '#5b6478',
                    600: '#474e63',
                    700: '#383e50',
                    800: '#252a3a',
                    900: '#161a27',
                    950: '#0a0d17',
                },
                // NavraCar V2 design tokens (docs/design-v2/DESIGN_SPEC.md §2).
                // Additive only — brand/amber/ink stay in place until each page
                // migrates, per docs/design-v2/IMPLEMENTATION_PLAN.md فاز یک.
                v2: {
                    bg: '#020B18',
                    surface: '#061426',
                    elevated: '#0A1B32',
                    primary: '#1677FF',
                    'primary-text': '#5B9BFF',
                    'primary-action': '#0D3FA8',
                    accent: '#20C7E9',
                    text: '#F8FAFC',
                    'text-muted': '#9AAAC1',
                    border: '#1A3554',
                    success: '#22C55E',
                    warning: '#EAB308',
                    error: '#EF4444',
                },
            },
            boxShadow: {
                soft: '0 10px 30px -12px rgba(16, 22, 60, .18)',
                'soft-lg': '0 24px 54px -20px rgba(16, 22, 60, .28)',
                'soft-dark': '0 10px 30px -12px rgba(0, 0, 0, .55)',
                'glow-amber': '0 8px 22px -8px rgba(255, 138, 30, .55)',
                'glow-brand': '0 8px 22px -8px rgba(41, 82, 224, .5)',
                'glow-v2': '0 8px 22px -8px rgba(22, 119, 255, .5)',
            },
            borderRadius: {
                xl2: '1.25rem',
            },
            animation: {
                'fade-up': 'fadeUp .4s ease both',
                'fade-in': 'fadeIn .25s ease both',
                'toast-in': 'toastIn .25s ease both',
            },
            keyframes: {
                fadeUp: {
                    '0%': { opacity: 0, transform: 'translateY(10px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' },
                },
                fadeIn: {
                    '0%': { opacity: 0 },
                    '100%': { opacity: 1 },
                },
                toastIn: {
                    '0%': { opacity: 0, transform: 'translateY(10px)' },
                    '100%': { opacity: 1, transform: 'translateY(0)' },
                },
            },
        },
    },

    plugins: [forms],
};
