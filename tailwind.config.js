import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                burgundy: {
                    DEFAULT: '#7A2D3B',
                    light: '#9E4455',
                    dark: '#5E2230',
                    soft: '#F0D8D0',
                },
                champagne: {
                    DEFAULT: '#FDF8F4',
                    dark: '#F0E8E0',
                },
                copper: '#D4956A',
                pmf: {
                    dark: '#2D1F24',
                    text: '#2D1F24',
                    // Contraste WCAG AA sobre superficies claras (champagne #FDF8F4
                    // y champagne-dark #F0E8E0). Ambos se usan a text-xs / text-[10px],
                    // que es texto normal: el umbral es 4.5:1, no 3:1.
                    secondary: '#70555C', // 6.33:1 champagne · 5.51:1 champagne-dark
                    muted: '#7D626B',     // 5.20:1 champagne · 4.52:1 champagne-dark
                    // Variante para texto sobre pmf-dark (footer, testimonios): el valor
                    // claro no sirve ahí, y el oscuro tampoco sirve aquí — son dos tokens.
                    'muted-dark': '#A89098', // 5.33:1 sobre pmf-dark
                    border: '#E5D8D0',
                },
            },
            fontFamily: {
                serif: ['"Source Serif 4"', ...defaultTheme.fontFamily.serif],
                sans: ['"Barlow Semi Condensed"', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
