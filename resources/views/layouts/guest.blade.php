<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Pustaka Dosen') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    {{-- Warna bilah status. Harus sama dengan sepia-900 (#0f172a) di
         tailwind.config.js, welcome.blade.php, layouts/app.blade.php, dan
         public/manifest.webmanifest. --}}
    <meta name="theme-color" content="#0B0E14">
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
<body class="font-sans antialiased bg-netral-100">

    {{-- Layout utama: form kiri + branding kanan --}}
    <div class="min-h-screen lg:flex">

        {{-- === PANEL KIRI: FORM (~40%) === --}}
        <main class="flex w-full flex-col justify-center bg-arang-deepest px-8 py-14 lg:w-[42%] lg:min-h-screen lg:px-14 xl:px-20">

            {{-- Logo — selalu tampil --}}
            <a href="{{ url('/') }}" class="anim-fade-up mb-12 flex items-center gap-3 self-start">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-9 w-9">
                <span class="font-display text-base font-semibold tracking-tight text-netral-100">Pustaka Dosen</span>
            </a>

            {{-- Heading form --}}
            <div class="anim-fade-up anim-delay-1 mb-8">
                <span class="divider-editorial mb-5"></span>
                <h2 class="font-display text-3xl font-semibold leading-tight text-netral-100">Masuk ke<br>Akun Anda</h2>
                <p class="mt-3 text-sm leading-relaxed text-netral-300">Akses koleksi e-book dan materi kuliah yang disusun dosen pengampu.</p>
            </div>

            {{-- Form slot --}}
            <div class="anim-fade-up anim-delay-2 w-full max-w-sm">
                {{ $slot }}
            </div>

            {{-- Footer form --}}
            <p class="anim-fade-up anim-delay-3 mt-10 text-xs text-netral-300">
                &copy; {{ date('Y') }} Pustaka Dosen &mdash; Koleksi pribadi dosen, akses mahasiswa terdaftar.
            </p>
        </main>

        {{-- === PANEL KANAN: BRANDING + LATAR PARTIKEL (~60%) === --}}
        <aside class="relative hidden overflow-hidden bg-arang-deep lg:flex lg:w-[58%]" aria-hidden="true">

            {{-- Canvas latar — full panel --}}
            <canvas id="particles-canvas" class="absolute inset-0 z-0 h-full w-full"></canvas>

            {{-- Konten editorial di atas canvas --}}
            <div class="relative z-10 flex h-full flex-col justify-between px-14 py-14 xl:px-20">

                {{-- Label overline atas --}}
                <p class="text-label font-semibold uppercase tracking-[0.22em] text-sienna-light">
                    Perpustakaan Digital
                </p>

                {{-- Headline serif raksasa —tengah vertikal --}}
                <div class="max-w-2xl">
                    <h1 class="headline-raksasa font-display text-netral-100">
                        Bahan<br>
                        <em class="not-italic text-sienna-light">bacaan</em><br>
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
        Latar partikel — Canvas 2D, tanpa pustaka luar.

        Sebelumnya bagian ini memakai Three.js r128 dari cdnjs. Tag <script>
        itu berada di <head> tanpa defer, sehingga halaman masuk tidak
        tergambar sama sekali sampai berkas 600 KB dari server pihak ketiga
        selesai diambil — atau sampai koneksinya timeout bila jaringan
        kampus memblokirnya.

        Isi animasinya hanya titik-titik berwarna dengan opasitas rendah:
        tidak ada geometri 3D, cahaya, maupun tekstur, jadi Canvas 2D sudah
        memadai. Kedalaman sumbu Z diganti variasi ukuran dan kecepatan.
    --}}
    <script>
    (function () {
        const canvas = document.getElementById('particles-canvas');
        if (!canvas) return;

        // Panel kanan hanya tampil pada lg ke atas (kelas "hidden lg:flex").
        // Di ponsel canvas-nya berukuran nol, jadi menghitung animasi di sana
        // hanya menghabiskan baterai tanpa satu piksel pun terlihat.
        if (!window.matchMedia('(min-width: 1024px)').matches) return;

        const konteks = canvas.getContext('2d');
        if (!konteks) return;

        const JUMLAH = 280;
        const WARNA = ['#2A2E3A', '#B85C38', '#1A1D26']; // arang-base, sienna, arang-deep
        const OPASITAS = 0.18;

        let lebar = 0;
        let tinggi = 0;
        const partikel = [];

        function ubahUkuran() {
            const w = canvas.clientWidth;
            const h = canvas.clientHeight;
            if (!w || !h) return false;

            const rasio = Math.min(window.devicePixelRatio || 1, 2);

            // Posisi partikel disimpan dalam piksel CSS; saat panel berubah
            // ukuran, posisinya diskalakan agar sebarannya tetap merata.
            if (lebar && tinggi) {
                const skalaX = w / lebar;
                const skalaY = h / tinggi;
                for (const p of partikel) {
                    p.x *= skalaX;
                    p.y *= skalaY;
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
            // Pengganti sumbu Z: makin "dekat", makin besar dan makin cepat.
            const kedalaman = 0.35 + Math.random() * 0.65;

            partikel.push({
                x: Math.random() * lebar,
                y: Math.random() * tinggi,
                dx: (Math.random() - 0.5) * 0.3 * kedalaman,
                dy: (Math.random() - 0.5) * 0.3 * kedalaman,
                jari: (0.9 + Math.random() * 1.4) * kedalaman,
                warna: WARNA[i % WARNA.length],
                kedalaman: kedalaman,
            });
        }

        let mx = 0, my = 0, tujuanX = 0, tujuanY = 0;

        function gambar() {
            konteks.clearRect(0, 0, lebar, tinggi);
            konteks.globalAlpha = OPASITAS;

            for (const p of partikel) {
                const gx = p.x + mx * 14 * p.kedalaman;
                const gy = p.y + my * 14 * p.kedalaman;

                konteks.beginPath();
                konteks.arc(gx, gy, p.jari, 0, Math.PI * 2);
                konteks.fillStyle = p.warna;
                konteks.fill();
            }
        }

        // Hormati preferensi sistem: satu frame diam, lalu berhenti. Panel
        // tetap punya tekstur, tanpa gerakan sama sekali.
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

            for (const p of partikel) {
                p.x += p.dx;
                p.y += p.dy;

                // Keluar satu sisi, masuk dari sisi seberang.
                if (p.x < -4) p.x = lebar + 4;
                else if (p.x > lebar + 4) p.x = -4;
                if (p.y < -4) p.y = tinggi + 4;
                else if (p.y > tinggi + 4) p.y = -4;
            }

            gambar();
        }

        requestAnimationFrame(langkah);
        window.addEventListener('resize', ubahUkuran);
    })();
    </script>
</body>
</html>