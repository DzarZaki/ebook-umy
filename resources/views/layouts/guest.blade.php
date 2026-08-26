<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Pustaka Dosen') }}</title>

    {{-- Script pencegah kedipan tema (FOUC) saat halaman dimuat --}}
    <script>
        (function() {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#101113">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Tidak ada lagi pustaka dari CDN di sini. Latar partikel di panel
         kanan digambar dengan Canvas 2D di bagian bawah berkas ini. --}}

    <style>
        /* Ukuran headline raksasa — clamp agar menyusut di layar kecil */
        .headline-raksasa {
            font-size: clamp(2.8rem, 5.5vw, 5.5rem);
            line-height: 0.92;
            letter-spacing: -0.03em;
            font-weight: 600;
        }
        /* Garis pemisah dekoratif bergaya editorial */
        .divider-editorial {
            display: block;
            width: 2.5rem;
            height: 2px;
            background-color: #f59e0b;
        }
        /* Masuk fade-in saat halaman load */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-fade-up {
            animation: fadeUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .anim-delay-1 { animation-delay: 0.08s; }
        .anim-delay-2 { animation-delay: 0.16s; }
        .anim-delay-3 { animation-delay: 0.24s; }
        @media (prefers-reduced-motion: reduce) {
            .anim-fade-up { animation: none; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-netral-100 dark:bg-arang-900 transition-colors">

    {{-- Layout utama: form kiri + branding kanan --}}
    <div class="min-h-screen lg:flex">

        {{-- === PANEL KIRI: FORM (~40%) === --}}
        <main class="flex w-full flex-col justify-center bg-white dark:bg-arang-900 px-8 py-14 lg:w-[42%] lg:min-h-screen lg:px-14 xl:px-20 border-r border-netral-200 dark:border-arang-600 transition-colors">

            {{-- Header Form & Theme Switcher --}}
            <div class="flex items-center justify-between mb-12">
                {{-- Logo — selalu tampil --}}
                <a href="{{ url('/') }}" class="anim-fade-up flex items-center gap-3 self-start">
                    <img src="{{ asset('images/logo.svg') }}" alt="" class="h-9 w-9">
                    <span class="font-display text-base font-semibold tracking-tight text-netral-900 dark:text-netral-100">Pustaka Dosen</span>
                </a>

                {{-- Mode Switcher di Guest Layout --}}
                <div x-data>
                    <button @click="$store.theme.setMode($store.theme.mode === 'dark' ? 'light' : ($store.theme.mode === 'light' ? 'system' : 'dark'))"
                            type="button"
                            :title="'Mode: ' + $store.theme.mode"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 text-netral-600 dark:text-netral-300 transition hover:text-jingga-600 dark:hover:text-jingga-400">
                        <svg x-show="$store.theme.mode === 'light'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg x-show="$store.theme.mode === 'dark'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="$store.theme.mode === 'system'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Heading form --}}
            <div class="anim-fade-up anim-delay-1 mb-8">
                <span class="divider-editorial mb-5"></span>
                <h2 class="font-display text-3xl font-semibold leading-tight text-netral-900 dark:text-netral-100">Masuk ke<br>Akun Anda</h2>
                <p class="mt-3 text-sm leading-relaxed text-netral-500 dark:text-netral-300">Akses koleksi e-book dan materi kuliah yang disusun dosen pengampu.</p>
            </div>

            {{-- Form slot --}}
            <div class="anim-fade-up anim-delay-2 w-full max-w-sm">
                {{ $slot }}
            </div>

            {{-- Footer form --}}
            <p class="anim-fade-up anim-delay-3 mt-10 text-xs text-netral-500 dark:text-netral-300">
                &copy; {{ date('Y') }} Pustaka Dosen &mdash; Koleksi pribadi dosen, akses mahasiswa terdaftar.
            </p>
        </main>

        {{-- === PANEL KANAN: BRANDING + LATAR PARTIKEL (~60%) === --}}
        <aside class="relative hidden overflow-hidden bg-arang-800 dark:bg-arang-900 lg:flex lg:w-[58%]" aria-hidden="true">

            {{-- Canvas latar — full panel --}}
            <canvas id="particles-canvas" class="absolute inset-0 z-0 h-full w-full"></canvas>

            {{-- Konten editorial di atas canvas --}}
            <div class="relative z-10 flex h-full flex-col justify-between px-14 py-14 xl:px-20">

                {{-- Label overline atas --}}
                <p class="text-label font-semibold uppercase tracking-[0.22em] text-jingga-400">
                    Perpustakaan Digital
                </p>

                {{-- Headline serif raksasa —tengah vertikal --}}
                <div class="max-w-2xl">
                    <h1 class="headline-raksasa font-display text-netral-100">
                        Bahan<br>
                        <em class="not-italic text-jingga-400">bacaan</em><br>
                        kuliah,<br>
                        disusun<br>
                        dosen.
                    </h1>
                    <p class="mt-8 max-w-xs text-sm leading-relaxed text-netral-300">
                        Koleksi e-book dan referensi akademik yang dikurasi langsung
                        oleh dosen pengampu. Dibaca di browser, tanpa mengunduh.
                    </p>
                </div>

                {{-- Label bawah --}}
                <div class="flex items-center gap-6">
                    <span class="divider-editorial"></span>
                    <p class="text-xs text-netral-300">Akses khusus mahasiswa terdaftar</p>
                </div>
            </div>
        </aside>

    </div>

    {{--
        Latar bintang sparkle — Canvas 2D, tanpa pustaka luar.
        Bintang 4 sudut bergaya modern dengan efek kelap-kelip lembut (twinkling)
        dan paralaks kursor untuk kedalaman visual.
    --}}
    <script>
    (function () {
        const canvas = document.getElementById('particles-canvas');
        if (!canvas) return;

        // Panel kanan hanya tampil pada lg ke atas (kelas "hidden lg:flex").
        if (!window.matchMedia('(min-width: 1024px)').matches) return;

        const konteks = canvas.getContext('2d');
        if (!konteks) return;

        const JUMLAH = 160;
        // Palet warna bintang terang & putih bercahaya
        const WARNA = ['#FFFFFF', '#FFFFFF', '#F8FAFC', '#FFFDF5', '#FEF3C7'];

        let lebar = 0;
        let tinggi = 0;
        const bintang = [];

        function ubahUkuran() {
            const w = canvas.clientWidth;
            const h = canvas.clientHeight;
            if (!w || !h) return false;

            const rasio = Math.min(window.devicePixelRatio || 1, 2);

            if (lebar && tinggi) {
                const skalaX = w / lebar;
                const skalaY = h / tinggi;
                for (const b of bintang) {
                    b.x *= skalaX;
                    b.y *= skalaY;
                }
            }

            lebar = w;
            tinggi = h;
            canvas.width = Math.round(w * rasio);
            canvas.height = Math.round(h * rasio);
            konteks.setTransform(rasio, 0, 0, rasio, 0, 0);

            return true;
        }

        if (!ubahUkuran()) return;

        for (let i = 0; i < JUMLAH; i++) {
            const kedalaman = 0.3 + Math.random() * 0.7; // variasi kedalaman
            const isSparkleBesar = Math.random() < 0.35; // 35% bintang berukuran sedang/besar

            bintang.push({
                x: Math.random() * lebar,
                y: Math.random() * tinggi,
                dx: (Math.random() - 0.5) * 0.18 * kedalaman,
                dy: (Math.random() - 0.5) * 0.18 * kedalaman,
                // Ukuran radius bintang
                jari: isSparkleBesar 
                    ? (3.0 + Math.random() * 4.5) * kedalaman 
                    : (1.2 + Math.random() * 2.0) * kedalaman,
                warna: WARNA[Math.floor(Math.random() * WARNA.length)],
                kedalaman: kedalaman,
                baseAlpha: 0.25 + Math.random() * 0.65,
                twinkleSpeed: 0.015 + Math.random() * 0.035,
                twinklePhase: Math.random() * Math.PI * 2,
            });
        }

        // Fungsi menggambar bintang 4-sudut (sparkle) modern
        function gambarSparkle(ctx, x, y, r, alpha, warna) {
            ctx.save();
            ctx.translate(x, y);
            ctx.fillStyle = warna;
            ctx.globalAlpha = Math.max(0, Math.min(1, alpha));

            // Bentuk bintang 4 sudut melengkung (modern diamond star)
            ctx.beginPath();
            ctx.moveTo(0, -r);
            ctx.quadraticCurveTo(0, 0, r, 0);
            ctx.quadraticCurveTo(0, 0, 0, r);
            ctx.quadraticCurveTo(0, 0, -r, 0);
            ctx.quadraticCurveTo(0, 0, 0, -r);
            ctx.closePath();
            ctx.fill();

            // Inti kilau halus untuk bintang yang lebih besar
            if (r > 3.2) {
                ctx.beginPath();
                ctx.arc(0, 0, r * 0.28, 0, Math.PI * 2);
                ctx.fillStyle = '#FFFFFF';
                ctx.globalAlpha = Math.min(1, alpha + 0.25);
                ctx.fill();
            }

            ctx.restore();
        }

        let mx = 0, my = 0, tujuanX = 0, tujuanY = 0;

        function gambar() {
            konteks.clearRect(0, 0, lebar, tinggi);

            for (const b of bintang) {
                const gx = b.x + mx * 18 * b.kedalaman;
                const gy = b.y + my * 18 * b.kedalaman;

                // Efek kelap-kelip lembut (sinusoidal pulse)
                const kelipAlpha = b.baseAlpha * (0.35 + 0.65 * (0.5 + 0.5 * Math.sin(b.twinklePhase)));

                gambarSparkle(konteks, gx, gy, b.jari, kelipAlpha, b.warna);
            }
        }

        // Hormati preferensi reduced-motion
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            gambar();
            window.addEventListener('resize', () => { if (ubahUkuran()) gambar(); });
            return;
        }

        document.addEventListener('mousemove', (e) => {
            tujuanX = (e.clientX / window.innerWidth) * 2 - 1;
            tujuanY = (e.clientY / window.innerHeight) * 2 - 1;
        }, { passive: true });

        let aktif = !document.hidden;
        document.addEventListener('visibilitychange', () => { aktif = !document.hidden; });

        function langkah() {
            requestAnimationFrame(langkah);
            if (!aktif) return;

            mx += (tujuanX - mx) * 0.04;
            my += (tujuanY - my) * 0.04;

            for (const b of bintang) {
                b.x += b.dx;
                b.y += b.dy;
                b.twinklePhase += b.twinkleSpeed;

                // Rotasi looping layar
                if (b.x < -8) b.x = lebar + 8;
                else if (b.x > lebar + 8) b.x = -8;
                if (b.y < -8) b.y = tinggi + 8;
                else if (b.y > tinggi + 8) b.y = -8;
            }

            gambar();
        }

        requestAnimationFrame(langkah);
        window.addEventListener('resize', ubahUkuran);
    })();
    </script>
</body>
</html>