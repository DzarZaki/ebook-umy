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
                // Amber emas — warna aksi premium. Menyala di atas latar gelap.
                jingga: {
                    50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d',
                    400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309',
                    800: '#92400e', 900: '#78350f',
                },
                // Deep slate — struktur, heading, panel gelap.
                sepia: {
                    50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1',
                    400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155',
                    800: '#1e293b', 900: '#0f172a',
                },
                // Cool gray — teks, border, background subtle.
                kabut: {
                    50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 300: '#d1d5db',
                    400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 700: '#374151',
                    800: '#1f2937', 900: '#111827',
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