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
                // Teks isi: humanis, mudah dibaca, tidak generik.
                sans: ['Karla', ...defaultTheme.fontFamily.sans],
                // Judul: serif berkarakter, memberi nuansa "perpustakaan".
                display: ['Fraunces', 'Georgia', 'serif'],
            },

            colors: {
                // Oranye — warna aksi utama.
                jingga: {
                    50: '#fff6ed', 100: '#ffead5', 200: '#fed7aa', 300: '#fdba74',
                    400: '#fb923c', 500: '#f27522', 600: '#dd5a0b', 700: '#b7440c',
                    800: '#923811', 900: '#762f11',
                },
                // Coklat — warna identitas & panel gelap.
                sepia: {
                    50: '#faf6f2', 100: '#f0e7de', 200: '#dfcdbc', 300: '#c7ab92',
                    400: '#ac8667', 500: '#946c4c', 600: '#7c5740', 700: '#5f4231',
                    800: '#4a3527', 900: '#2e211a',
                },
                // Abu-abu hangat — latar & teks.
                kabut: {
                    50: '#faf9f7', 100: '#f4f2ef', 200: '#e7e4df', 300: '#d5d0c9',
                    400: '#a8a29a', 500: '#78716b', 600: '#57534d', 700: '#44403a',
                    800: '#292524', 900: '#1c1917',
                },
            },
        },
    },

    plugins: [forms],
};