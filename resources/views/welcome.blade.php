<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pustaka Dosen — Perpustakaan Digital Mahasiswa</title>
    <meta name="description" content="Koleksi e-book dan artikel kuliah yang disusun sendiri oleh dosen pengampu, dapat dibaca gratis oleh mahasiswa.">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#5f4231">
<link rel="apple-touch-icon" href="/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,600,700|karla:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

    {{-- Bilah atas --}}
    <header class="border-b border-kabut-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-8 w-8">
                <span class="font-display text-base font-semibold text-kabut-900">Pustaka Dosen</span>
            </div>

            <nav class="flex items-center gap-3 text-sm">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="rounded-sm bg-jingga-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-jingga-700">
                        Masuk ke Beranda
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-2 font-medium text-kabut-600 transition-colors hover:text-kabut-900">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-sm bg-jingga-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-jingga-700">
                        Daftar
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- Hero asimetris: teks lebar di kiri, informasi ringkas di kanan --}}
    <section class="border-b border-kabut-200 bg-white">
        <div class="mx-auto grid max-w-6xl gap-12 px-6 py-16 lg:grid-cols-12 lg:py-24">
            <div class="lg:col-span-7">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-jingga-700">
                    Koleksi pribadi dosen pengampu
                </p>

                <h1 class="mt-5 font-display text-4xl font-semibold leading-[1.15] text-kabut-900 sm:text-5xl">
                    Bahan bacaan kuliah yang ditulis dosen Anda sendiri&mdash;bukan hasil unggahan sembarangan.
                </h1>

                <p class="mt-6 max-w-xl text-base leading-relaxed text-kabut-600">
                    Setiap e-book dan artikel di sini diunggah langsung oleh dosen pengampu, lalu
                    dikelompokkan menurut program studi. Mahasiswa cukup masuk dengan email kampus,
                    membaca di peramban, dan mengunduh bila dosen mengizinkan.
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="{{ route('register') }}"
                       class="rounded-sm bg-jingga-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-jingga-700">
                        Daftar dengan email @umy.ac.id
                    </a>
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-kabut-700 underline underline-offset-4 hover:text-kabut-900">
                        Sudah punya akun
                    </a>
                </div>
            </div>

            {{-- Kolom kanan: kartu informasi bergaya katalog --}}
            <div class="lg:col-span-5">
                <div class="border border-kabut-300 bg-kabut-50">
                    <div class="border-b border-kabut-300 bg-sepia-900 px-6 py-4">
                        <p class="font-display text-sm font-semibold text-kabut-50">Program studi yang tersedia</p>
                    </div>

                    <ul class="divide-y divide-kabut-200">
                        @forelse ($daftarProdi as $prodi)
                            <li class="flex items-center justify-between px-6 py-4">
                                <span class="text-sm font-medium text-kabut-800">{{ $prodi->name }}</span>
                                <span class="text-xs uppercase tracking-wider text-kabut-500">{{ $prodi->slug }}</span>
                            </li>
                        @empty
                            <li class="px-6 py-4 text-sm text-kabut-500">Belum ada program studi terdaftar.</li>
                        @endforelse
                    </ul>

                    <div class="border-t border-kabut-300 px-6 py-4">
                        <p class="text-xs leading-relaxed text-kabut-500">
                            Koleksi bertanda <strong class="text-kabut-700">Umum</strong> dapat dibaca
                            mahasiswa dari seluruh program studi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Penjelasan alur, disusun mendatar bergaya editorial --}}
    <section class="mx-auto max-w-6xl px-6 py-16">
        <h2 class="font-display text-2xl font-semibold text-kabut-900">Cara kerjanya</h2>

        <div class="mt-8 grid gap-px border border-kabut-200 bg-kabut-200 sm:grid-cols-3">
            <div class="bg-white p-6">
                <p class="font-display text-3xl text-jingga-600">01</p>
                <h3 class="mt-3 text-base font-semibold text-kabut-900">Daftar sekali, pilih prodi</h3>
                <p class="mt-2 text-sm leading-relaxed text-kabut-600">
                    Gunakan email kampus. Program studi yang Anda pilih menentukan koleksi yang tampil,
                    dan hanya dapat diubah oleh dosen pengelola.
                </p>
            </div>
            <div class="bg-white p-6">
                <p class="font-display text-3xl text-jingga-600">02</p>
                <h3 class="mt-3 text-base font-semibold text-kabut-900">Baca di peramban</h3>
                <p class="mt-2 text-sm leading-relaxed text-kabut-600">
                    Seluruh berkas berformat PDF dan terbuka langsung di halaman baca,
                    tanpa perlu memasang aplikasi tambahan.
                </p>
            </div>
            <div class="bg-white p-6">
                <p class="font-display text-3xl text-jingga-600">03</p>
                <h3 class="mt-3 text-base font-semibold text-kabut-900">Unduh sesuai izin dosen</h3>
                <p class="mt-2 text-sm leading-relaxed text-kabut-600">
                    Sebagian buku dapat diunduh utuh, sebagian hanya bab tertentu,
                    dan sebagian lagi khusus dibaca di tempat.
                </p>
            </div>
        </div>
    </section>

    <footer class="border-t border-kabut-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-6 py-6 text-xs text-kabut-500">
            <p>Pustaka Dosen &middot; Universitas Muhammadiyah Yogyakarta</p>
            <p>Akses terbatas untuk pemilik email @umy.ac.id</p>
        </div>
    </footer>
</body>
</html>