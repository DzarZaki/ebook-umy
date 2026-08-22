import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
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
                // Amber Lentera — Aksen Jingga Editorial (Pilihan 3)
                // Hangat, presisi arsitektural, berbobot untuk teks di mode terang dan berpendar lembut di mode gelap.
                jingga: {
                    50: '#FDF7F4',      // bg highlight sangat lembut
                    100: '#FBF0EB',     // bg badge / surface tint terang
                    200: '#F6DDD2',     // border aksen terang
                    300: '#EEBAA6',     // aksen medium-light
                    400: '#E06A3B',     // primer di mode gelap (amber lentera pendar)
                    500: '#CC562A',     // medium dark
                    600: '#B34E28',     // DEFAULT / primer di mode terang (tinta amber pekat)
                    700: '#943F1F',     // hover / active mode terang
                    800: '#753118',     // deep tone
                    900: '#522210',     // deepest tone
                },
                // Monokrom Arsitektural / Obsidian Grafit (Dasar Gelap)
                arang: {
                    50: '#F4F4F5',      // abu terang netral
                    100: '#E4E4E7',     // pemisah / teks terang
                    200: '#D4D4D8',     // border terang
                    300: '#A1A1AA',     // teks redup mode gelap
                    400: '#71717A',     // teks muted
                    500: '#52525B',     // pembatas sekunder
                    600: '#282A30',     // garis pemisah / border mode gelap
                    700: '#191A1E',     // kartu / surface mode gelap
                    800: '#131417',     // footer / nav / panel sekunder mode gelap
                    900: '#101113',     // kanvas latar utama mode gelap (obsidian monokrom)
                },
                // Studio Mineral / Warm Paper Netral (Dasar Terang & Teks)
                netral: {
                    50: '#FAF9F8',      // kartu / permukaan paling terang
                    100: '#F4F4F3',     // kanvas latar utama mode terang (studio mineral)
                    200: '#E1E0DD',     // garis batas / border halus mode terang
                    300: '#B5B4B0',     // border medium / ikon muted
                    400: '#919194',     // teks sekunder mode gelap
                    500: '#606063',     // teks sekunder mode terang
                    600: '#424245',     // teks subjudul mode terang
                    700: '#2B2B2D',     // teks judul mode terang
                    800: '#1C1C1E',     // teks utama sangat pekat
                    900: '#141415',     // teks utama mode terang / hitam tinta cetak
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