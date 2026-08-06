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
    <link href="https://fonts.bunny.net/css?family=fraunces:400,600,700|karla:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,42%)_1fr]">

        {{-- Panel identitas (kiri) — hanya tampil di layar lebar --}}
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-sepia-900 px-12 py-14 lg:flex">
            <div class="pointer-events-none absolute -right-24 top-24 h-72 w-72 rounded-full bg-jingga-600/20"></div>
            <div class="pointer-events-none absolute -left-16 bottom-10 h-56 w-56 rounded-full bg-sepia-700/40"></div>

            <a href="{{ url('/') }}" class="relative z-10 flex items-center gap-3">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-10 w-10">
                <span class="font-display text-lg font-semibold text-kabut-50">Pustaka Dosen</span>
            </a>

            <div class="relative z-10 max-w-md">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-jingga-400">Perpustakaan Digital</p>
                <h1 class="mt-4 font-display text-4xl font-semibold leading-tight text-kabut-50">
                    Bahan bacaan kuliah, disusun langsung oleh dosen pengampu.
                </h1>
                <p class="mt-5 text-sm leading-relaxed text-kabut-300">
                    Koleksi e-book dan artikel untuk mahasiswa Pendidikan Agama Islam
                    dan Manajemen. Dibaca langsung di peramban, tanpa biaya.
                </p>
            </div>

            <p class="relative z-10 text-xs text-kabut-400">
                Koleksi pribadi dosen &middot; Akses khusus mahasiswa terdaftar
            </p>
        </aside>

        {{-- Panel formulir (kanan) --}}
        <main class="flex min-h-screen items-center justify-center bg-kabut-50 px-6 py-12">
            <div class="w-full max-w-md">
                <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/logo.svg') }}" alt="" class="h-9 w-9">
                    <span class="font-display text-lg font-semibold text-kabut-900">Pustaka Dosen</span>
                </a>

                <div class="border border-kabut-200 bg-white p-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>