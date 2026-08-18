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
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Playfair Display', 'Georgia', 'serif'],
            },

            colors: {
                // Burnt sienna — aksen tunggal untuk aksi dan penekanan.
                // Hangat seperti kulit buku dan tinta lama, bukan oranye AI.
                sienna: {
                    light: '#D97556',   // lencana, state disabled
                    DEFAULT: '#B85C38', // tombol, tautan, aksen utama
                    dark: '#934A2D',    // hover, pressed
                },
                // Charcoal-navy — dasar gelap dengan sedikit kehangatan.
                // Bukan hitam murni, bukan ungu-biru AI.
                arang: {
                    deepest: '#0B0E14', // body background
                    deep: '#1A1D26',    // card, panel
                    base: '#2A2E3A',    // border, divider
                },
                // Neutral warm — teks dan surface dengan sedikit kehangatan.
                netral: {
                    100: '#E8E6E3',     // text primary
                    200: '#C8C5C0',     // text secondary (medium emphasis)
                    300: '#9B9893',     // text tertiary (low emphasis)
                    400: '#6B6863',     // text disabled, subtle labels
                    500: '#4A4744',     // borders, very subtle elements
                },
            },

                        // ---------------------------------------------------------
            // Fase 3.5 — skala tipe editorial
            // clamp() dipakai agar judul raksasa mengecil sendiri di
            // ponsel tanpa perlu satu pun kelas responsif. Angka
            // tengahnya berbasis vw, jadi ia mengikuti lebar layar.
            // ---------------------------------------------------------
            fontSize: {
                'raksasa': ['clamp(2.5rem, 9vw, 6.5rem)', {
                    lineHeight: '0.9',
                    letterSpacing: '-0.035em',
                    fontWeight: '600',
                }],
                'besar': ['clamp(1.875rem, 5vw, 3.5rem)', {
                    lineHeight: '0.98',
                    letterSpacing: '-0.025em',
                }],
                'judul': ['clamp(1.375rem, 2.6vw, 2rem)', {
                    lineHeight: '1.12',
                    letterSpacing: '-0.015em',
                }],
                // Label kecil berhuruf besar untuk menomori bagian,
                // meniru cara indeks buku menandai babnya.
                'label': ['0.6875rem', {
                    lineHeight: '1',
                    letterSpacing: '0.14em',
                }],
            },

            transitionTimingFunction: {
                // Satu kurva untuk seluruh aplikasi. Gerak yang konsisten
                // terasa seperti satu tangan yang mengerjakannya.
                'kertas': 'cubic-bezier(0.16, 1, 0.3, 1)',
            },

            transitionDuration: {
                '550': '550ms',
            },

            screens: {
                // Ponsel lebar: rak boleh menampilkan buku ketiga.
                'xs': '480px',
            },
        },
    },

    plugins: [forms],
};