<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Pustaka Dosen') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#5f4231">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Three.js CDN --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" integrity="sha512-dLxUelApnYxpLt6K2iomGngnHO83iUvZytA3YjDUCjT0HDOHKXnVYdf3hU4JjM8uEhxf9nD1/ey98U3t2vZ0qQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

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
<body class="font-sans antialiased bg-kabut-50">

    {{-- Layout utama: form kiri + branding kanan --}}
    <div class="min-h-screen lg:flex">

        {{-- === PANEL KIRI: FORM (~40%) === --}}
        <main class="flex w-full flex-col justify-center bg-sepia-900 px-8 py-14 lg:w-[42%] lg:min-h-screen lg:px-14 xl:px-20">

            {{-- Logo — selalu tampil --}}
            <a href="{{ url('/') }}" class="anim-fade-up mb-12 flex items-center gap-3 self-start">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-9 w-9">
                <span class="font-display text-base font-semibold tracking-tight text-kabut-50">Pustaka Dosen</span>
            </a>

            {{-- Heading form --}}
            <div class="anim-fade-up anim-delay-1 mb-8">
                <span class="divider-editorial mb-5"></span>
                <h2 class="font-display text-3xl font-semibold leading-tight text-kabut-50">Masuk ke<br>Akun Anda</h2>
                <p class="mt-3 text-sm leading-relaxed text-kabut-400">Akses koleksi e-book dan materi kuliah yang disusun dosen pengampu.</p>
            </div>

            {{-- Form slot --}}
            <div class="anim-fade-up anim-delay-2 w-full max-w-sm">
                {{ $slot }}
            </div>

            {{-- Footer form --}}
            <p class="anim-fade-up anim-delay-3 mt-10 text-xs text-kabut-500">
                &copy; {{ date('Y') }} Pustaka Dosen &mdash; Koleksi pribadi dosen, akses mahasiswa terdaftar.
            </p>
        </main>

        {{-- === PANEL KANAN: BRANDING + THREE.JS (~60%) === --}}
        <aside class="relative hidden overflow-hidden bg-sepia-800 lg:flex lg:w-[58%]" aria-hidden="true">

            {{-- Three.js canvas — full panel --}}
            <canvas id="particles-canvas" class="absolute inset-0 z-0 h-full w-full"></canvas>

            {{-- Konten editorial di atas canvas --}}
            <div class="relative z-10 flex h-full flex-col justify-between px-14 py-14 xl:px-20">

                {{-- Label overline atas --}}
                <p class="text-label font-semibold uppercase tracking-[0.22em] text-jingga-400">
                    Perpustakaan Digital
                </p>

                {{-- Headline serif raksasa —tengah vertikal --}}
                <div class="max-w-2xl">
                    <h1 class="headline-raksasa font-display text-kabut-50">
                        Bahan<br>
                        <em class="not-italic text-jingga-400">bacaan</em><br>
                        kuliah,<br>
                        disusun<br>
                        dosen.
                    </h1>
                    <p class="mt-8 max-w-xs text-sm leading-relaxed text-kabut-400">
                        Koleksi e-book dan referensi akademik yang dikurasi langsung
                        oleh dosen pengampu. Dibaca di browser, tanpa mengunduh.
                    </p>
                </div>

                {{-- Label bawah --}}
                <div class="flex items-center gap-6">
                    <span class="divider-editorial"></span>
                    <p class="text-xs text-kabut-500">Akses khusus mahasiswa terdaftar</p>
                </div>
            </div>
        </aside>

    </div>

    {{-- Three.js Particle Animation --}}
    <script>
    (function () {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) return;

        const canvas = document.getElementById('particles-canvas');
        if (!canvas) return;

        const scene    = new THREE.Scene();
        const camera   = new THREE.PerspectiveCamera(70, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false });

        function resize() {
            const w = canvas.clientWidth, h = canvas.clientHeight;
            if (!w || !h) return;
            renderer.setSize(w, h, false);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            camera.aspect = w / h;
            camera.updateProjectionMatrix();
        }
        resize();
        camera.position.z = 55;

        // Particle grid
        const COUNT  = 280;
        const pos    = new Float32Array(COUNT * 3);
        const col    = new Float32Array(COUNT * 3);
        const vel    = [];
        const c1     = new THREE.Color(0x334155); // slate-700
        const c2     = new THREE.Color(0xf59e0b); // amber-500
        const c3     = new THREE.Color(0x1e293b); // slate-800

        for (let i = 0; i < COUNT; i++) {
            pos[i*3]   = (Math.random() - 0.5) * 110;
            pos[i*3+1] = (Math.random() - 0.5) * 80;
            pos[i*3+2] = (Math.random() - 0.5) * 30;
            vel.push({
                x: (Math.random() - 0.5) * 0.018,
                y: (Math.random() - 0.5) * 0.018,
                z: (Math.random() - 0.5) * 0.008,
            });
            const pick = [c1, c2, c3][i % 3];
            col[i*3] = pick.r; col[i*3+1] = pick.g; col[i*3+2] = pick.b;
        }

        const geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
        geo.setAttribute('color',    new THREE.BufferAttribute(col, 3));

        const mat = new THREE.PointsMaterial({
            size: 2.5,
            vertexColors: true,
            transparent: true,
            opacity: 0.18,
            sizeAttenuation: true,
        });

        const points = new THREE.Points(geo, mat);
        scene.add(points);

        // Mouse parallax — hanya di panel kanan
        let mx = 0, my = 0;
        document.addEventListener('mousemove', (e) => {
            mx = (e.clientX / window.innerWidth)  * 2 - 1;
            my = -(e.clientY / window.innerHeight) * 2 + 1;
        });

        // Pause saat tab tidak aktif
        let active = true;
        document.addEventListener('visibilitychange', () => { active = !document.hidden; });

        // Loop
        function tick() {
            requestAnimationFrame(tick);
            if (!active) return;

            const p = points.geometry.attributes.position.array;
            for (let i = 0; i < COUNT; i++) {
                p[i*3]   += vel[i].x;
                p[i*3+1] += vel[i].y;
                p[i*3+2] += vel[i].z;
                if (Math.abs(p[i*3])   > 60) vel[i].x *= -1;
                if (Math.abs(p[i*3+1]) > 45) vel[i].y *= -1;
                if (Math.abs(p[i*3+2]) > 20) vel[i].z *= -1;
            }
            points.geometry.attributes.position.needsUpdate = true;

            points.rotation.x += (my * 0.04 - points.rotation.x) * 0.04;
            points.rotation.y += (mx * 0.04 - points.rotation.y) * 0.04;

            renderer.render(scene, camera);
        }
        tick();

        window.addEventListener('resize', resize);
    })();
    </script>
</body>
</html>
