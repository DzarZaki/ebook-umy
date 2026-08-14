<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pustaka Dosen — Perpustakaan Digital Mahasiswa</title>
    <meta name="description" content="Koleksi e-book dan artikel kuliah yang disusun sendiri oleh dosen pengampu, dapat dibaca gratis oleh mahasiswa.">

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#0f172a">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|playfair-display:400,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-sepia-900 text-kabut-100">

    {{-- =================================================================
         BILAH ATAS
         ================================================================= --}}
    <header class="absolute inset-x-0 top-0 z-30">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
            <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-8 w-8">
                <span class="font-display text-base font-semibold tracking-tight text-kabut-50">Pustaka Dosen</span>
            </a>

            <nav class="flex items-center gap-5">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="bg-sepia-800 px-5 py-2.5 text-label font-semibold uppercase tracking-[0.18em] text-kabut-50 transition-colors duration-200 hover:bg-sepia-900">
                        Masuk ke Beranda
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="sapu-bawah text-label font-semibold uppercase tracking-[0.18em] text-kabut-300">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="bg-sepia-800 px-5 py-2.5 text-label font-semibold uppercase tracking-[0.18em] text-kabut-50 transition-colors duration-200 hover:bg-sepia-900">
                        Daftar
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    {{-- =================================================================
         HERO — buku raksasa yang dapat dibuka

         Isi halamannya HTML sungguhan: dapat diseleksi, dapat diklik, dan
         terbaca mesin pencari. Alpine hanya menyimpan satu keadaan buka
         atau tutup; seluruh gerakannya CSS.

         terbuka = null  -> belum disentuh, buku tertutup, sampul menunggu
         terbuka = true  -> terbuka
         terbuka = false -> ditutup kembali oleh pengguna
         ================================================================= --}}
    <section class="tekstur-kertas relative overflow-hidden border-b border-sepia-700 bg-sepia-900">
        <div class="mx-auto max-w-5xl px-6 pb-24 pt-32 lg:px-10 lg:pb-28 lg:pt-40">

            <div x-data="{ terbuka: null }"
                 x-init="setTimeout(() => { if (terbuka === null) terbuka = true }, 5000)"
                 class="buku-panggung">

                <div class="buku-muka"
                     :class="terbuka === null ? '' : (terbuka ? 'terbuka' : 'tertutup')">

                    <div class="buku-muka__bayang" aria-hidden="true"></div>

                    {{-- Halaman kiri --}}
                    <div class="buku-muka__lembar buku-muka__lembar--kiri">
                        <div class="flex h-full flex-col justify-between p-8 sm:p-10 lg:p-12">
                            <div>
                                <p class="text-label font-semibold uppercase tracking-[0.24em] text-jingga-700">
                                    Koleksi pribadi dosen pengampu
                                </p>

                                <h1 class="mt-6 font-display text-2xl font-semibold leading-[1.2] text-kabut-900 sm:text-3xl lg:text-4xl">
                                    Bahan bacaan kuliah yang ditulis dosen Anda sendiri&mdash;bukan hasil unggahan sembarangan.
                                </h1>
                            </div>

                            <p class="mt-8 hidden font-display text-sm text-kabut-400 md:block" aria-hidden="true">01</p>
                        </div>
                    </div>

                    {{-- Halaman kanan --}}
                    <div class="buku-muka__lembar buku-muka__lembar--kanan">
                        <div class="flex h-full flex-col justify-between p-8 sm:p-10 lg:p-12">
                            <div>
                                <p class="text-base leading-relaxed text-kabut-600">
                                    Setiap e-book dan artikel di sini diunggah langsung oleh dosen pengampu, lalu
                                    dikelompokkan menurut program studi. Mahasiswa cukup mendaftar dengan email pribadi,
                                    membaca langsung di situs ini, dan mengunduh bila dosen mengizinkan.
                                </p>

                                <div class="mt-8 flex flex-wrap items-center gap-5">
                                    <a href="{{ route('register') }}"
                                       class="bg-jingga-600 px-6 py-3.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-jingga-700">
                                        Daftar dengan email pribadi
                                    </a>
                                    <a href="{{ route('login') }}"
                                       class="sapu-bawah text-sm font-semibold text-kabut-700">
                                        Sudah punya akun
                                    </a>
                                </div>
                            </div>

                            <p class="mt-8 hidden text-right font-display text-sm text-kabut-400 md:block" aria-hidden="true">02</p>
                        </div>
                    </div>

                    <div class="buku-muka__jilid" aria-hidden="true"></div>

                    {{-- Sampul depan --}}
                    <button type="button"
                            class="buku-muka__sampul"
                            @click="terbuka = true"
                            :disabled="terbuka === true"
                            :aria-expanded="terbuka === true ? 'true' : 'false'"
                            aria-label="Buka buku dan tampilkan keterangan">
                        <span class="flex h-full flex-col justify-between p-8 sm:p-10 lg:p-12">
                            <span class="block">
                                <span class="block text-label font-semibold uppercase tracking-[0.24em] text-jingga-300">
                                    Perpustakaan digital
                                </span>

                                <span class="mt-6 block font-display text-3xl font-semibold leading-tight text-kabut-50 lg:text-4xl">
                                    Pustaka Dosen
                                </span>

                                <span class="mt-6 block h-px w-16 bg-jingga-600"></span>

                                <span class="mt-6 block max-w-xs text-sm leading-relaxed text-kabut-300">
                                    Ditulis dosen pengampu, dibaca mahasiswa.
                                </span>
                            </span>

                            <span class="block text-label uppercase tracking-[0.2em] text-kabut-400">
                                Klik untuk membuka
                            </span>
                        </span>

                        <span class="buku-muka__sampul-balik" aria-hidden="true"></span>
                    </button>
                </div>

                {{-- Kendali buka-tutup, hanya di layar lebar --}}
                <div class="mt-10 hidden items-center justify-center gap-6 md:flex">
                    <button type="button"
                            x-cloak
                            x-show="terbuka === true"
                            @click="terbuka = false"
                            class="sapu-bawah text-label font-semibold uppercase tracking-[0.2em] text-kabut-500">
                        Tutup buku
                    </button>

                    <button type="button"
                            x-cloak
                            x-show="terbuka === false"
                            @click="terbuka = true"
                            class="sapu-bawah text-label font-semibold uppercase tracking-[0.2em] text-kabut-500">
                        Buka buku
                    </button>
                </div>
            </div>

            <p data-muncul class="mt-8 text-center text-label uppercase tracking-[0.2em] text-kabut-500">
                Rak digital &middot; Disusun dosen, dibaca mahasiswa
            </p>
        </div>
    </section>

    {{-- =================================================================
         PITA BERJALAN
         ================================================================= --}}
    <div class="border-b border-sepia-900 bg-sepia-800 py-4 text-kabut-50">
        <div class="marquee">
            <div class="marquee__isi" data-gandakan style="--laju: 52s">
                <span class="font-display text-lg font-semibold tracking-tight">Diunggah dosen</span>
                <span class="text-jingga-300">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Gratis untuk mahasiswa</span>
                <span class="text-jingga-300">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Buka dan langsung baca</span>
                <span class="text-jingga-300">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Tanpa aplikasi tambahan</span>
                <span class="text-jingga-300">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Sesuai program studi</span>
                <span class="text-jingga-300">&#9679;</span>
            </div>
        </div>
    </div>

    {{-- =================================================================
         CARA KERJANYA
         ================================================================= --}}
    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
        <div class="flex items-baseline justify-between gap-6 border-b border-sepia-700 pb-5">
            <h2 class="font-display text-judul font-semibold text-kabut-50">Cara kerjanya</h2>
            <span class="text-label uppercase tracking-[0.2em] text-kabut-400">Tiga langkah</span>
        </div>

        @php
            $langkah = [
                [
                    'judul' => 'Daftar sekali, pilih prodi',
                    'isi' => 'Cukup email pribadi seperti Gmail. Program studi yang Anda pilih menentukan koleksi yang tampil, dan hanya dapat diubah oleh dosen pengelola.',
                ],
                [
                    'judul' => 'Baca tanpa mengunduh',
                    'isi' => 'Seluruh berkas berformat PDF dan terbuka langsung di halaman baca, tanpa perlu memasang aplikasi tambahan.',
                ],
                [
                    'judul' => 'Unduh sesuai izin dosen',
                    'isi' => 'Sebagian buku dapat diunduh utuh, sebagian hanya bab tertentu, dan sebagian lagi khusus dibaca di tempat.',
                ],
            ];
        @endphp

        <div class="divide-y divide-sepia-700">
            @foreach ($langkah as $i => $baris)
                <article data-muncul style="--tunda: {{ $i * 100 }}ms"
                         class="grid gap-4 py-10 sm:grid-cols-12 sm:gap-8">
                    <p class="angka-tepi font-display text-besar font-semibold leading-none text-jingga-600 sm:col-span-2">
                        {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                    </p>

                    <h3 class="font-display text-xl font-semibold text-kabut-50 sm:col-span-4">
                        {{ $baris['judul'] }}
                    </h3>

                    <p class="max-w-xl text-sm leading-relaxed text-kabut-400 sm:col-span-6">
                        {{ $baris['isi'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- =================================================================
         PROGRAM STUDI
         ================================================================= --}}
    <section class="border-y border-sepia-700 bg-sepia-800/50">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-8">

                <div class="lg:col-span-4">
                    <h2 class="font-display text-judul font-semibold leading-tight text-kabut-50">
                        Program studi yang tersedia
                    </h2>

                    <p class="mt-5 max-w-sm text-sm leading-relaxed text-kabut-400">
                        Koleksi bertanda <strong class="font-semibold text-kabut-100">Umum</strong> dapat dibaca
                        mahasiswa dari seluruh program studi.
                    </p>

                    <p class="mt-8 font-display text-besar font-semibold leading-none text-jingga-400">
                        {{ str_pad($daftarProdi->count(), 2, '0', STR_PAD_LEFT) }}
                    </p>
                    <p class="mt-1 text-label uppercase tracking-[0.2em] text-kabut-500">
                        Program studi terdaftar
                    </p>
                </div>

                <div class="lg:col-span-8">
                    <ol class="divide-y divide-sepia-700 border-y border-sepia-700">
                        @forelse ($daftarProdi as $i => $prodi)
                            <li data-muncul style="--tunda: {{ min($i, 8) * 60 }}ms"
                                class="flex items-baseline gap-5 py-5">
                                <span class="w-8 shrink-0 font-display text-sm font-semibold text-kabut-400">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <span class="flex-1 font-display text-lg font-semibold text-kabut-50">
                                    {{ $prodi->name }}
                                </span>

                                <span class="text-label uppercase tracking-[0.18em] text-kabut-500">
                                    {{ $prodi->slug }}
                                </span>
                            </li>
                        @empty
                            <li class="py-8 text-sm text-kabut-500">Belum ada program studi terdaftar.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- =================================================================
         AJAKAN PENUTUP
         ================================================================= --}}
    <section class="tekstur-kertas bg-sepia-900/50">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
            <div data-muncul class="max-w-3xl">
                <h2 class="judul-raksasa font-display font-semibold leading-[0.95] text-kabut-50">
                    Mulai membaca hari ini.
                </h2>

                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <a href="{{ route('register') }}"
                       class="bg-jingga-600 px-7 py-4 text-sm font-semibold text-white transition-colors duration-200 hover:bg-jingga-700">
                        Buat akun mahasiswa
                    </a>
                    <a href="{{ route('login') }}"
                       class="sapu-bawah text-sm font-semibold text-kabut-300">
                        Masuk ke akun yang sudah ada
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t border-sepia-700 bg-sepia-900 text-kabut-400">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-8 text-label uppercase tracking-[0.18em] lg:px-10">
            <p>Pustaka Dosen &middot; Perpustakaan digital dosen pengampu</p>
            <p>Akses terbatas untuk mahasiswa terdaftar</p>
        </div>
    </footer>
</body>
</html>