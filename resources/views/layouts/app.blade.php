<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Judul tab kini bisa diisi per halaman lewat <x-slot name="title">.
         Selama belum diisi, isinya sama seperti sebelumnya. --}}
    <title>{{ isset($title) ? $title.' · '.config('app.name', 'Pustaka Dosen') : config('app.name', 'Pustaka Dosen') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">

           {{-- Warna bilah aplikasi saat dipasang sebagai PWA: sepia-900 (#0f172a).
         Nilai ini harus sama di welcome.blade.php, layouts/guest.blade.php,
         dan public/manifest.webmanifest — kalau berbeda, bilah status ponsel
         berubah warna saat pengguna berpindah halaman. --}}
    <meta name="theme-color" content="#0B0E14">
    <link rel="apple-touch-icon" href="/images/icon-192.png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    {{-- Lewati navigasi: tidak terlihat sampai pengguna keyboard menekan Tab
         sekali. Tanpa ini, setiap perpindahan halaman memaksa mereka
         menelusuri seluruh menu sebelum mencapai isi halaman. --}}
    <a href="#konten"
       class="sr-only rounded bg-white px-4 py-2 text-sm font-semibold text-jingga-700 ring-2 ring-jingga-600 focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50">
        Lewati ke konten
    </a>

    {{-- Pembungkus ini dulu memakai bg-kabut-50. Warnanya dilepas supaya
         dasar kertas (body::before dan body::after, Langkah 90) terlihat
         menembus seluruh tinggi halaman tanpa terputus.

         Semua halaman mahasiswa memakai kerangka ini, jadi satu perubahan di
         sini berlaku untuk beranda, katalog, koleksi, dan detail buku. --}}
    <div class="min-h-screen">
        @include('layouts.navigation')

        {{-- Header halaman: latar putihnya dilepas, pemisahnya kini garis
             rambut sewarna tinta yang memudar, bukan blok warna. --}}
        @isset($header)
            <header class="border-b garis-tinta">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main id="konten">
            {{ $slot }}
        </main>

        {{-- Kaki halaman ikut tembus pandang. Sedikit lebih gelap dari dasar
             lewat rgba tipis, cukup untuk menutup halaman tanpa menjadi pita
             warna baru. --}}
        <footer class="mt-16 border-t garis-tinta bg-sepia-900/80">
            <div class="mx-auto max-w-7xl px-4 py-8 text-xs text-kabut-500 sm:px-6 lg:px-8">
                Pustaka Dosen &middot; Dikelola mandiri oleh dosen pengampu
            </div>
        </footer>
    </div>
</body>
</html>