<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pustaka Dosen — Perpustakaan Digital Mahasiswa</title>
    <meta name="description" content="Koleksi e-book dan artikel kuliah yang disusun sendiri oleh dosen pengampu, dapat dibaca gratis oleh mahasiswa.">

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
</head>
<body class="font-sans antialiased bg-netral-100 dark:bg-arang-900 text-netral-900 dark:text-netral-100 transition-colors">

    {{-- =================================================================
         BILAH ATAS
         ================================================================= --}}
    <header class="absolute inset-x-0 top-0 z-30">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
            <a href="{{ route('beranda') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.svg') }}" alt="" class="h-8 w-8">
                <span class="font-display text-base font-semibold tracking-tight text-netral-900 dark:text-netral-100">Pustaka Dosen</span>
            </a>

            <nav class="flex items-center gap-4">
                {{-- Theme toggle di header landing page --}}
                <div x-data>
                    <button @click="$store.theme.setMode($store.theme.mode === 'dark' ? 'light' : ($store.theme.mode === 'light' ? 'system' : 'dark'))"
                            type="button"
                            :title="'Mode: ' + $store.theme.mode"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-700 text-netral-600 dark:text-netral-300 transition hover:text-jingga-600 dark:hover:text-jingga-400">
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

                @auth
                    <a href="{{ route('dashboard') }}"
                       class="bg-white dark:bg-arang-700 border border-netral-200 dark:border-arang-600 px-5 py-2.5 text-label font-semibold uppercase tracking-[0.18em] text-netral-900 dark:text-netral-100 transition-colors duration-200 hover:bg-netral-100 dark:hover:bg-arang-600">
                        Masuk ke Beranda
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="sapu-bawah text-label font-semibold uppercase tracking-[0.18em] text-netral-700 dark:text-netral-200">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="bg-white dark:bg-arang-700 border border-netral-200 dark:border-arang-600 px-5 py-2.5 text-label font-semibold uppercase tracking-[0.18em] text-netral-900 dark:text-netral-100 transition-colors duration-200 hover:bg-netral-100 dark:hover:bg-arang-600">
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
    <section class="tekstur-kertas relative overflow-hidden border-b border-netral-200 dark:border-arang-600 bg-white/50 dark:bg-arang-900/60 transition-colors">
        <div class="mx-auto max-w-5xl px-6 pb-24 pt-32 lg:px-10 lg:pb-28 lg:pt-40">

            <div x-data="{ terbuka: null }"
                 x-init="setTimeout(() => { if (terbuka === null) terbuka = true }, 5000)"
                 class="buku-panggung">

                <div class="buku-muka"
                     :class="terbuka === null ? '' : (terbuka ? 'terbuka' : 'tertutup')">

                    <div class="buku-muka__bayang" aria-hidden="true"></div>

                    {{-- Halaman kiri --}}
                    <div class="buku-muka__lembar buku-muka__lembar--kiri border border-netral-200 dark:border-transparent">
                        <div class="flex h-full flex-col justify-between p-8 sm:p-10 lg:p-12">
                            <div>
                                <p class="text-label font-semibold uppercase tracking-[0.24em] text-jingga-600">
                                    Koleksi pribadi dosen pengampu
                                </p>

                                <h1 class="mt-6 font-display text-2xl font-semibold leading-[1.2] text-netral-900 sm:text-3xl lg:text-4xl">
                                    Bahan bacaan kuliah yang ditulis dosen Anda sendiri&mdash;bukan hasil unggahan sembarangan.
                                </h1>
                            </div>

                            <p class="mt-8 hidden font-display text-sm text-netral-400 md:block" aria-hidden="true">01</p>
                        </div>
                    </div>

                    {{-- Halaman kanan --}}
                    <div class="buku-muka__lembar buku-muka__lembar--kanan border border-netral-200 dark:border-transparent">
                        <div class="flex h-full flex-col justify-between p-8 sm:p-10 lg:p-12">
                            <div>
                                <p class="text-base leading-relaxed text-netral-600">
                                    Setiap e-book dan artikel di sini diunggah langsung oleh dosen pengampu, lalu
                                    dikelompokkan menurut program studi. Mahasiswa cukup mendaftar dengan email pribadi,
                                    membaca langsung di situs ini, dan mengunduh bila dosen mengizinkan.
                                </p>

                                <div class="mt-8 flex flex-wrap items-center gap-5">
                                    <a href="{{ route('register') }}"
                                       class="bg-jingga-600 px-6 py-3.5 text-sm font-semibold text-white hover:bg-jingga-700 btn-press rounded shadow-sm">
                                        Daftar dengan email pribadi
                                    </a>
                                    <a href="{{ route('login') }}"
                                       class="sapu-bawah text-sm font-semibold text-netral-700">
                                        Sudah punya akun
                                    </a>
                                </div>
                            </div>

                            <p class="mt-8 hidden text-right font-display text-sm text-netral-400 md:block" aria-hidden="true">02</p>
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
                                <span class="block text-label font-semibold uppercase tracking-[0.24em] text-jingga-400">
                                    Perpustakaan digital
                                </span>

                                <span class="mt-6 block font-display text-3xl font-semibold leading-tight text-white lg:text-4xl">
                                    Pustaka Dosen
                                </span>

                                <span class="mt-6 block h-px w-16 bg-jingga-500"></span>

                                <span class="mt-6 block max-w-xs text-sm leading-relaxed text-netral-200">
                                    Ditulis dosen pengampu, dibaca mahasiswa.
                                </span>
                            </span>

                            <span class="block text-label uppercase tracking-[0.2em] text-netral-300">
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
                            class="sapu-bawah text-label font-semibold uppercase tracking-[0.2em] text-netral-500 dark:text-netral-400">
                        Tutup buku
                    </button>

                    <button type="button"
                            x-cloak
                            x-show="terbuka === false"
                            @click="terbuka = true"
                            class="sapu-bawah text-label font-semibold uppercase tracking-[0.2em] text-netral-500 dark:text-netral-400">
                        Buka buku
                    </button>
                </div>
            </div>

            <p data-muncul class="mt-8 text-center text-label uppercase tracking-[0.2em] text-netral-500 dark:text-netral-400">
                Rak digital &middot; Disusun dosen, dibaca mahasiswa
            </p>
        </div>
    </section>

    {{-- =================================================================
         PITA BERJALAN
         ================================================================= --}}
    <div class="border-b border-netral-200 dark:border-arang-600 bg-white/70 dark:bg-arang-700 py-4 text-netral-900 dark:text-netral-100 transition-colors">
        <div class="marquee">
            <div class="marquee__isi" data-gandakan style="--laju: 52s">
                <span class="font-display text-lg font-semibold tracking-tight">Diunggah dosen</span>
                <span class="text-jingga-600 dark:text-jingga-400">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Gratis untuk mahasiswa</span>
                <span class="text-jingga-600 dark:text-jingga-400">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Buka dan langsung baca</span>
                <span class="text-jingga-600 dark:text-jingga-400">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Tanpa aplikasi tambahan</span>
                <span class="text-jingga-600 dark:text-jingga-400">&#9679;</span>
                <span class="font-display text-lg font-semibold tracking-tight">Sesuai program studi</span>
                <span class="text-jingga-600 dark:text-jingga-400">&#9679;</span>
            </div>
        </div>
    </div>

    {{-- =================================================================
         PROFIL DOSEN PENGAMPU
         Satu bagian per dosen yang menyalakan penampilan publiknya;
         susunannya selang-seling agar halaman berirama majalah.
         ================================================================= --}}
    @foreach ($daftarProfilDosen as $i => $profilDosen)
        <section class="border-b border-netral-200 dark:border-arang-600 bg-white/50 dark:bg-arang-800/40 backdrop-blur-sm transition-colors">
            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
                <div class="grid gap-12 lg:grid-cols-12 lg:items-center">

                    {{-- Foto & Identitas Singkat Dosen --}}
                    <div class="lg:col-span-5 {{ $i % 2 === 1 ? 'lg:order-2' : '' }}" data-muncul>
                        <div class="relative mx-auto max-w-sm lg:mx-0">
                            {{-- Bingkai Foto Bergaya Studio / Editorial --}}
                            <div class="overflow-hidden rounded-2xl border-2 border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700/80 shadow-md">
                                @if ($profilDosen->photoUrl())
                                    <img src="{{ $profilDosen->photoUrl() }}" alt="Foto {{ $profilDosen->nama_lengkap }}"
                                         class="aspect-[4/5] w-full object-cover">
                                @else
                                    <div class="flex aspect-[4/5] w-full items-center justify-center font-display text-7xl font-semibold text-jingga-600 dark:text-jingga-400 bg-gradient-to-br from-jingga-50 to-netral-100 dark:from-arang-800 dark:to-arang-700">
                                        {{ Str::upper(Str::substr($profilDosen->user?->name ?? 'D', 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            @if ($profilDosen->quote)
                                <div class="mt-4 rounded-xl border border-jingga-200/80 dark:border-jingga-700/40 bg-jingga-50/90 dark:bg-jingga-900/20 p-4 text-xs italic leading-relaxed text-jingga-800 dark:text-jingga-300 shadow-sm">
                                    &ldquo;{{ $profilDosen->quote }}&rdquo;
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Keterangan, Sambutan & Tautan Akademik Dosen --}}
                    <div class="lg:col-span-7 {{ $i % 2 === 1 ? 'lg:order-1' : '' }}" data-muncul data-tunda="120">
                        <p class="text-label font-semibold uppercase tracking-[0.24em] text-jingga-600 dark:text-jingga-400">
                            Dosen Pengampu &middot; Kurator Bahan Ajar
                        </p>

                        <h2 class="mt-3 font-display text-besar font-semibold text-netral-900 dark:text-netral-50">
                            {{ $profilDosen->nama_lengkap }}
                        </h2>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-medium text-netral-600 dark:text-netral-400">
                            @if ($profilDosen->academic_position)
                                <span>{{ $profilDosen->academic_position }}</span>
                            @endif
                            @if ($profilDosen->user?->prodi)
                                <span>&middot; {{ $profilDosen->user->prodi->name }}</span>
                            @endif
                            @if ($profilDosen->nidn)
                                <span>&middot; NIDN: {{ $profilDosen->nidn }}</span>
                            @endif
                        </div>

                        @if ($profilDosen->bio)
                            <div class="mt-6 text-sm leading-relaxed text-netral-700 dark:text-netral-300">
                                {!! nl2br(e($profilDosen->bio)) !!}
                            </div>
                        @else
                            <p class="mt-6 text-sm leading-relaxed text-netral-600 dark:text-netral-300">
                                Selamat datang di repositori bahan bacaan dan buku kuliah. Seluruh materi di situs ini
                                dikurasi khusus untuk menunjang proses perkuliahan dan pendalaman materi mahasiswa.
                            </p>
                        @endif

                        {{-- Tag Bidang Keahlian --}}
                        @if (! empty($profilDosen->daftar_keahlian))
                            <div class="mt-6">
                                <p class="text-xs font-semibold uppercase tracking-wider text-netral-500 dark:text-netral-400">Bidang Kajian &amp; Riset:</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($profilDosen->daftar_keahlian as $keahlian)
                                        <span class="rounded-md border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700 px-2.5 py-1 text-xs font-medium text-netral-700 dark:text-netral-300 badge-category">
                                            {{ $keahlian }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tautan Profil Akademik / Publikasi --}}
                        @php
                            $tautan = array_filter([
                                ['label' => 'Google Scholar', 'url' => $profilDosen->google_scholar_url],
                                ['label' => 'Scopus / Sinta', 'url' => $profilDosen->scopus_url],
                                ['label' => 'LinkedIn', 'url' => $profilDosen->linkedin_url],
                                ['label' => 'Website Pribadi', 'url' => $profilDosen->website_url],
                            ], fn($t) => ! empty($t['url']));
                        @endphp

                        @if (! empty($tautan))
                            <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-netral-200 dark:border-arang-600 pt-5">
                                <span class="text-xs font-semibold uppercase tracking-wider text-netral-500 dark:text-netral-400">Profil Riset:</span>
                                @foreach ($tautan as $t)
                                    <a href="{{ $t['url'] }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 rounded border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 px-3 py-1.5 text-xs font-semibold text-netral-700 dark:text-netral-300 shadow-sm transition hover:border-jingga-500 hover:text-jingga-600 dark:hover:text-jingga-400">
                                        {{ $t['label'] }} &nearr;
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    {{-- =================================================================
         CARA KERJANYA
         ================================================================= --}}
    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
        <div class="flex items-baseline justify-between gap-6 border-b border-netral-200 dark:border-arang-600 pb-5">
            <h2 class="font-display text-judul font-semibold text-netral-900 dark:text-netral-100">Cara kerjanya</h2>
            <span class="text-label uppercase tracking-[0.2em] text-netral-500 dark:text-netral-400">Tiga langkah</span>
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

        <div class="divide-y divide-netral-200 dark:divide-arang-600">
            @foreach ($langkah as $i => $baris)
                <article data-muncul style="--tunda: {{ $i * 100 }}ms"
                         class="grid gap-4 py-10 sm:grid-cols-12 sm:gap-8">
                    <p class="angka-tepi font-display text-besar font-semibold leading-none sm:col-span-2">
                        {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                    </p>

                    <h3 class="font-display text-xl font-semibold text-netral-900 dark:text-netral-100 sm:col-span-4">
                        {{ $baris['judul'] }}
                    </h3>

                    <p class="max-w-xl text-sm leading-relaxed text-netral-600 dark:text-netral-400 sm:col-span-6">
                        {{ $baris['isi'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- =================================================================
         PROGRAM STUDI
         ================================================================= --}}
    <section class="border-y border-netral-200 dark:border-arang-600 bg-white/40 dark:bg-arang-700/50 transition-colors">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-8">

                <div class="lg:col-span-4">
                    <h2 class="font-display text-judul font-semibold leading-tight text-netral-900 dark:text-netral-100">
                        Program studi yang tersedia
                    </h2>

                    <p class="mt-5 max-w-sm text-sm leading-relaxed text-netral-600 dark:text-netral-400">
                        Koleksi bertanda <strong class="font-semibold text-netral-900 dark:text-netral-100">Umum</strong> dapat dibaca
                        mahasiswa dari seluruh program studi.
                    </p>

                    <p class="mt-8 font-display text-besar font-semibold leading-none text-jingga-600 dark:text-jingga-400">
                        {{ str_pad($daftarProdi->count(), 2, '0', STR_PAD_LEFT) }}
                    </p>
                    <p class="mt-1 text-label uppercase tracking-[0.2em] text-netral-500 dark:text-netral-400">
                        Program studi terdaftar
                    </p>
                </div>

                <div class="lg:col-span-8">
                    <ol class="divide-y divide-netral-200 dark:divide-arang-600 border-y border-netral-200 dark:border-arang-600">
                        @forelse ($daftarProdi as $i => $prodi)
                            <li data-muncul style="--tunda: {{ min($i, 8) * 60 }}ms"
                                class="flex items-baseline gap-5 py-5">
                                <span class="w-8 shrink-0 font-display text-sm font-semibold text-netral-500 dark:text-netral-400">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <span class="flex-1 font-display text-lg font-semibold text-netral-900 dark:text-netral-100">
                                    {{ $prodi->name }}
                                </span>

                                <span class="text-label uppercase tracking-[0.18em] text-netral-500 dark:text-netral-400">
                                    {{ $prodi->slug }}
                                </span>
                            </li>
                        @empty
                            <li class="py-8 text-sm text-netral-500 dark:text-netral-400">Belum ada program studi terdaftar.</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </div>
    </section>

    {{-- =================================================================
         AJAKAN PENUTUP
         ================================================================= --}}
    <section class="tekstur-kertas bg-netral-100/60 dark:bg-arang-900/50 transition-colors">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">
            <div data-muncul class="max-w-3xl">
                <h2 class="judul-raksasa font-display font-semibold leading-[0.95] text-netral-900 dark:text-netral-100">
                    Mulai membaca hari ini.
                </h2>

                <div class="mt-10 flex flex-wrap items-center gap-6">
                    <a href="{{ route('register') }}"
                       class="bg-jingga-600 px-7 py-4 text-sm font-semibold text-white transition-colors duration-200 hover:bg-jingga-700 rounded btn-press shadow-sm">
                        Buat akun mahasiswa
                    </a>
                    <a href="{{ route('login') }}"
                       class="sapu-bawah text-sm font-semibold text-netral-700 dark:text-netral-200">
                        Masuk ke akun yang sudah ada
                    </a>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-900 text-netral-600 dark:text-netral-400 transition-colors">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-6 py-8 text-label uppercase tracking-[0.18em] lg:px-10">
            <p>Pustaka Dosen &middot; Perpustakaan digital dosen pengampu</p>
            <p>Akses terbatas untuk mahasiswa terdaftar</p>
        </div>
    </footer>
</body>
</html>