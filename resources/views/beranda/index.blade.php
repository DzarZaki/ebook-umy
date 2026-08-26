<x-app-layout>
    <x-slot name="title">Beranda</x-slot>

    @php
        $pengguna = auth()->user();
        $jam = (int) now()->format('H');
        $sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 19 ? 'Selamat sore' : 'Selamat malam'));
        // Nama depan saja. "Selamat pagi, Dzar" terdengar seperti manusia;
        // "Selamat pagi, Dzar Zaki Fazluri" terdengan seperti surat tagihan.
        $namaDepan = \Illuminate\Support\Str::before($pengguna->name, ' ');

        $adaIsi = $lanjutkan->isNotEmpty() || $tersimpan->isNotEmpty() || $terbaru->isNotEmpty();
        $sedangDibaca = $lanjutkan->first();
    @endphp

    {{-- =====================================================================
         1. KEPALA
         Tipe raksasa, asimetris, dan sengaja melebihi lebar isi di kanan.
         Angka besar bergaris tepi di kanan atas adalah cara Obys menandai
         bagian; di sini ia menghitung buku yang bisa Anda baca.
         ===================================================================== --}}
    <section class="tekstur-kertas relative overflow-hidden border-b border-netral-200 dark:border-arang-600 bg-white/60 dark:bg-arang-800 transition-colors">
        <div class="mx-auto max-w-7xl px-4 pb-14 pt-12 sm:px-6 sm:pb-20 sm:pt-16 lg:px-8">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-end lg:justify-between">

                <div class="max-w-3xl" data-muncul>
                    <p class="text-label font-semibold uppercase text-jingga-600 dark:text-jingga-400">
                        {{ $sapaan }} &middot; {{ now()->translatedFormat('l, j F Y') }}
                    </p>

                    <h1 class="judul-raksasa mt-4 text-raksasa text-netral-900 dark:text-netral-50">
                        {{ $namaDepan }},<br>
                        <span class="text-netral-500 dark:text-netral-400">mau baca apa</span><br>
                        hari ini?
                    </h1>

                    <p class="mt-6 max-w-lg text-base leading-relaxed text-netral-600 dark:text-netral-400">
                        @if ($pengguna->isMahasiswa())
                            Rak {{ $pengguna->prodi?->name ?? 'program studi Anda' }},
                            beserta bacaan umum dan koleksi pribadi Anda.
                        @else
                            Rak pribadi Anda. Pengelolaan buku ada di menu Kelola.
                        @endif
                    </p>

                    {{-- Pintu aktif: cari judul, penulis, sampai isi buku. --}}
                    <form action="{{ route('katalog.index') }}" method="GET" role="search"
                          class="mt-8 flex max-w-md items-stretch gap-2" data-muncul data-tunda="120">
                        <label for="cari-cepat" class="sr-only">Cari buku</label>
                        <x-text-input id="cari-cepat" name="q" type="search"
                                      placeholder="Cari judul, penulis, atau isinya…"
                                      class="block w-full" />
                        <button type="submit"
                                class="shrink-0 rounded-md bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2">
                            Cari
                        </button>
                    </form>
                </div>

                {{-- Pencacahan. Angka besar, label kecil, garis rambut.
                    dt didahulukan di DOM untuk urutan screen reader;
                    flex-col-reverse mempertahankan angka di atas. --}}
                <dl class="flex shrink-0 gap-8 lg:gap-10" data-muncul data-tunda="180">
                    <div class="flex flex-col-reverse">
                        <dt class="mt-2 text-label font-semibold uppercase text-netral-500">Di rak Anda</dt>
                        <dd class="font-display text-4xl font-semibold leading-none text-netral-900 dark:text-netral-50 sm:text-5xl">
                            {{ str_pad($terbaru->count() + $tersimpan->count(), 2, '0', STR_PAD_LEFT) }}
                        </dd>
                    </div>
                    <div class="flex flex-col-reverse border-l border-netral-200 dark:border-arang-600 pl-8 lg:pl-10">
                        <dt class="mt-2 text-label font-semibold uppercase text-netral-500">Sedang dibaca</dt>
                        <dd class="font-display text-4xl font-semibold leading-none text-netral-900 dark:text-netral-50 sm:text-5xl">
                            {{ str_pad($lanjutkan->count(), 2, '0', STR_PAD_LEFT) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         2. RAK KOSONG
         ===================================================================== --}}
    @unless ($adaIsi)
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-md text-center" data-muncul>
                <p class="text-besar text-netral-900 dark:text-netral-50">Raknya masih kosong</p>
                <p class="mt-4 text-sm leading-relaxed text-netral-600 dark:text-netral-400">
                    Belum ada bacaan yang tersedia untuk program studi Anda.
                    Coba lihat katalog, atau tanyakan kepada dosen pengampu.
                </p>
                <a href="{{ route('katalog.index') }}"
                   class="mt-8 inline-flex cursor-pointer items-center gap-2 rounded bg-jingga-600 dark:bg-jingga-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                    Buka katalog
                </a>
            </div>
        </div>
    @endunless

    {{-- =====================================================================
         3. YANG SEDANG DIBACA — satu buku, besar
         ===================================================================== --}}
    @if ($sedangDibaca)
        @php
            $bukuUtama = $sedangDibaca['buku'];
            $persenUtama = $sedangDibaca['persen'] ?? 0;
            $halamanUtama = $sedangDibaca['halaman'];
            $totalUtama = $sedangDibaca['total'];
        @endphp

        <section class="relative overflow-hidden border-b border-netral-200 dark:border-arang-600 bg-white/40 dark:bg-arang-700/20 transition-colors">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-jingga-500/40 to-transparent" aria-hidden="true"></div>
            <div class="pointer-events-none absolute right-0 top-1/2 h-[600px] w-[600px] -translate-y-1/2 translate-x-1/2 rounded-full bg-jingga-400/5 blur-3xl" aria-hidden="true"></div>
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                <div class="grid items-center gap-10 sm:gap-14 lg:grid-cols-12">

                    <div class="order-2 lg:order-1 lg:col-span-7" data-muncul>
                        <p class="text-label font-semibold uppercase text-jingga-600 dark:text-jingga-400">
                            Sedang dibaca
                        </p>

                        <h2 class="mt-4 text-besar text-netral-900 dark:text-netral-50">
                            <a href="{{ route('katalog.show', $bukuUtama) }}" class="sapu-bawah">
                                {{ $bukuUtama->title }}
                            </a>
                        </h2>

                        <p class="mt-3 text-sm text-netral-600 dark:text-netral-400">
                            {{ $bukuUtama->author ?: 'Tanpa penulis' }}
                            &middot; {{ $bukuUtama->isUmum() ? 'Umum' : ($bukuUtama->prodi?->name ?? 'Umum') }}
                        </p>

                        {{-- Kemajuan sebagai pernyataan --}}
                        <div class="mt-8 max-w-md">
                            <div class="flex items-end justify-between gap-4">
                                <p class="font-display text-3xl font-semibold leading-none text-netral-900 dark:text-netral-50">
                                    {{ $persenUtama > 0 ? $persenUtama . '%' : 'Baru dimulai' }}
                                </p>
                                <p class="text-sm text-netral-600 dark:text-netral-400">
                                    Halaman {{ $halamanUtama }}@if ($totalUtama) dari {{ $totalUtama }}@endif
                                </p>
                            </div>

                            <div class="mt-3 h-1 w-full overflow-hidden bg-netral-200 dark:bg-arang-600"
                                 role="progressbar"
                                 aria-valuenow="{{ $persenUtama }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-label="Kemajuan membaca {{ $bukuUtama->title }}">
                                <div class="h-full bg-jingga-600 dark:bg-jingga-500 transition-[width] duration-700 ease-kertas motion-reduce:transition-none"
                                     style="width: {{ max(2, $persenUtama) }}%"></div>
                            </div>
                        </div>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <a href="{{ route('katalog.baca', $bukuUtama) }}"
                               class="cursor-pointer rounded bg-jingga-600 dark:bg-jingga-500 px-6 py-3 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none shadow-sm">
                                Lanjutkan membaca
                            </a>
                            <x-tombol-simpan :buku="$bukuUtama"
                                             :tersimpan="$pengguna->telahMenyimpan($bukuUtama)" />
                        </div>
                    </div>

                    {{-- Buku 3D besar, sedikit dimiringkan dari sumbu halaman. --}}
                    <div class="order-1 lg:order-2 lg:col-span-5" data-muncul data-tunda="140">
                        <a href="{{ route('katalog.baca', $bukuUtama) }}"
                           aria-label="Lanjutkan membaca {{ $bukuUtama->title }}"
                           class="mx-auto block w-44 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-4 sm:w-56 lg:ml-auto lg:mr-0 lg:w-64">
                            <x-buku-3d :buku="$bukuUtama" />
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- =====================================================================
         STRIP LANJUTKAN — sisa bacaan setengah jalan selain hero.
         Setiap kartu mendarat tepat di halaman terakhir lewat ?halaman=.
         ===================================================================== --}}
    @php($lanjutanSisa = $lanjutkan->slice(1)->take(3)->values())
    @if ($lanjutanSisa->isNotEmpty())
        <section aria-label="Lanjutkan bacaan lainnya"
                 class="border-b border-netral-200 dark:border-arang-600 bg-white/50 dark:bg-arang-800/40 transition-colors">
            <div class="mx-auto flex max-w-7xl flex-wrap gap-3 px-4 py-5 sm:px-6 lg:px-8">
                @foreach ($lanjutanSisa as $bacaan)
                    <a href="{{ route('katalog.baca', ['buku' => $bacaan['buku'], 'halaman' => $bacaan['halaman']]) }}"
                       class="group flex min-w-[240px] flex-1 cursor-pointer items-center gap-3 rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/60 px-4 py-3 shadow-sm transition-colors duration-150 hover:border-jingga-400 dark:hover:border-jingga-500 sm:flex-none sm:basis-[calc(33%-0.5rem)] focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2">

                        {{-- Ring kemajuan --}}
                        <svg viewBox="0 0 36 36" class="h-9 w-9 shrink-0 -rotate-90" role="img"
                             aria-label="Terbaca {{ $bacaan['persen'] ?? 0 }} persen">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3"
                                    class="stroke-netral-200 dark:stroke-arang-600" />
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3" stroke-linecap="round"
                                    stroke-dasharray="{{ round(2 * M_PI * 15.5, 1) }}"
                                    stroke-dashoffset="{{ round(2 * M_PI * 15.5 * (1 - ($bacaan['persen'] ?? 0) / 100), 1) }}"
                                    class="stroke-jingga-600 transition-[stroke-dashoffset] duration-700 ease-kertas dark:stroke-jingga-400" />
                        </svg>

                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-netral-900 dark:text-netral-50 group-hover:text-jingga-700 dark:group-hover:text-jingga-400">
                                {{ $bacaan['buku']->title }}
                            </span>
                            <span class="block text-xs text-netral-500 dark:text-netral-400">
                                Hal. {{ $bacaan['halaman'] }}{{ $bacaan['total'] ? ' dari '.$bacaan['total'] : '' }}
                                &middot; {{ $bacaan['persen'] ?? 0 }}%
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- =====================================================================
         SEDANG RAMAI — koleksi terhangat prodi pekan ini.
         Skor gabungan unduhan dan penyimpanan tujuh hari terakhir:
         bukti sosial bahwa rak ini hidup dipakai teman seangkatan.
         ===================================================================== --}}
    @if ($ramai->isNotEmpty())
        <section class="border-b border-netral-200 dark:border-arang-600 bg-white/30 dark:bg-arang-700/20 py-14 sm:py-20 transition-colors">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4" data-muncul>
                    <div>
                        <p class="text-label font-semibold uppercase text-jingga-600 dark:text-jingga-400">Rak 01</p>
                        <h2 class="mt-2 text-judul text-netral-900 dark:text-netral-50">Sedang ramai</h2>
                        <p class="mt-1 text-sm text-netral-500 dark:text-netral-400">
                            Paling banyak diunduh dan disimpan tujuh hari terakhir.
                        </p>
                    </div>
                </div>

                <ol class="grid gap-px overflow-hidden rounded-lg border border-netral-200 dark:border-arang-600 bg-netral-200 dark:bg-arang-600 shadow-sm dark:shadow-none sm:grid-cols-2 lg:grid-cols-3" data-muncul>
                    @foreach ($ramai as $urut => $buku)
                        <li class="group flex items-center gap-4 bg-white p-5 dark:bg-arang-700/80 transition-colors">
                            <span class="w-7 shrink-0 font-display text-xl text-netral-300 dark:text-netral-500">
                                {{ str_pad($urut + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>

                            <a href="{{ route('katalog.show', $buku) }}" tabindex="-1" aria-hidden="true"
                               class="h-16 w-12 shrink-0 overflow-hidden rounded-sm border border-netral-200 dark:border-arang-600 bg-netral-100 dark:bg-arang-800">
                                @if ($buku->coverUrl())
                                    <img src="{{ $buku->coverUrl() }}" alt="" loading="lazy" decoding="async"
                                         class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center font-display text-lg font-semibold text-jingga-600 dark:text-jingga-400">
                                        {{ $buku->inisial() }}
                                    </span>
                                @endif
                            </a>

                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-semibold text-netral-900 dark:text-netral-50">
                                    <a href="{{ route('katalog.show', $buku) }}"
                                       class="sapu-bawah cursor-pointer focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-500">
                                        {{ $buku->title }}
                                    </a>
                                </h3>
                                <p class="truncate text-xs text-netral-500 dark:text-netral-400">
                                    {{ $buku->author ?: 'Tanpa penulis' }} &middot; {{ $buku->isUmum() ? 'Umum' : ($buku->prodi?->name ?? 'Umum') }}
                                </p>
                            </div>

                            <span class="shrink-0 rounded bg-jingga-50 dark:bg-jingga-900/30 px-2 py-1 text-xs font-semibold text-jingga-700 dark:text-jingga-300">
                                {{ $buku->kehangatan }}&times;
                            </span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- =====================================================================
         4. RAK BERGULIR — Tersimpan
         ===================================================================== --}}
    @if ($tersimpan->isNotEmpty())
        <section class="border-b border-netral-200 dark:border-arang-600 bg-white/30 dark:bg-arang-700/20 py-14 sm:py-20 transition-colors" data-rak>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div data-muncul>
                        <p class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">
                            Rak 02
                        </p>
                        <h2 class="mt-2 text-judul text-netral-900 dark:text-netral-50">
                            Koleksi Saya
                            <span class="angka-tepi ml-1">({{ $tersimpan->count() }})</span>
                        </h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2" data-rak-kendali hidden>
                            <button type="button" data-rak-mundur aria-label="Geser rak ke kiri"
                                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-600 dark:text-netral-400 transition-colors duration-150 hover:border-netral-400 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 motion-reduce:transition-none">
                                &larr;
                            </button>
                            <button type="button" data-rak-maju aria-label="Geser rak ke kanan"
                                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-600 dark:text-netral-400 transition-colors duration-150 hover:border-netral-400 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 motion-reduce:transition-none">
                                &rarr;
                            </button>
                        </div>

                        <a href="{{ route('koleksi.index') }}"
                           class="sapu-bawah cursor-pointer text-sm font-semibold text-jingga-600 dark:text-jingga-400 transition-colors duration-150 hover:text-jingga-700 dark:hover:text-jingga-300 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            Lihat semua
                        </a>
                    </div>
                </div>

                {{-- pb-10 memberi ruang bagi bayangan buku agar tidak terpotong
                     oleh batas gulungan. --}}
                <div class="relative">
                <div class="rak-gulir -mx-4 gap-8 px-4 pb-10 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8"
                     data-rak-isi data-tahap="70">
                    @foreach ($tersimpan as $buku)
                        <div class="w-32 xs:w-36 sm:w-40" data-muncul>
                            <a href="{{ route('katalog.show', $buku) }}"
                               class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-4">
                                <x-buku-3d :buku="$buku" />
                            </a>

                            <p class="mt-5 line-clamp-2 text-sm font-semibold leading-snug text-netral-900 dark:text-netral-50">
                                {{ $buku->title }}
                            </p>

                            {{--
                                Tombol lepas HARUS ada di sini. Semua buku di
                                rak ini pasti tersimpan, jadi pita ini selalu
                                berbentuk form DELETE — itulah satu-satunya
                                cara mengeluarkan buku dari beranda.

                                Ia diletakkan di LUAR tautan di atas, karena
                                <form> di dalam <a> adalah HTML tidak sah.
                            --}}
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="min-w-0 truncate text-xs text-netral-500 dark:text-netral-400">
                                    {{ $buku->author ?: 'Tanpa penulis' }}
                                </span>

                                <x-tombol-simpan :buku="$buku"
                                                 :tersimpan="true"
                                                 gaya="ikon"
                                                 class="shrink-0" />
                            </div>
                        </div>
                    @endforeach
                </div>
                </div>{{-- /relative wrapper rak-gulir --}}
            </div>
        </section>
    @endif

    {{-- =====================================================================
         5. MARQUEE
         ===================================================================== --}}
    <div class="marquee border-b border-netral-200 dark:border-arang-600 bg-white/70 dark:bg-arang-700 py-4 transition-colors" aria-hidden="true">
        <div class="marquee__isi" data-gandakan style="--laju: 46s">
            @foreach (['Membaca', 'Belajar', 'Menandai', 'Mengulang', 'Memahami', 'Mencatat', 'Menemukan'] as $kata)
                <span class="flex items-center whitespace-nowrap">
                    <span class="px-6 font-display text-lg text-netral-900/80 dark:text-netral-50/80">{{ $kata }}</span>
                    <span class="text-jingga-600 dark:text-jingga-400">&#9679;</span>
                </span>
            @endforeach
        </div>
    </div>

    {{-- =====================================================================
         6. BARU DITAMBAHKAN — indeks bernomor
         ===================================================================== --}}
    @if ($terbaru->isNotEmpty())
        <section class="bg-white/40 dark:bg-arang-700/10 py-14 sm:py-20 transition-colors">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div class="mb-8 flex flex-wrap items-end justify-between gap-4" data-muncul>
                    <div>
                        <p class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">Rak 03</p>
                        <h2 class="mt-2 text-judul text-netral-900 dark:text-netral-50">
                            Baru ditambahkan
                            <span class="angka-tepi ml-1">({{ $terbaru->count() }})</span>
                        </h2>
                    </div>

                    <a href="{{ route('katalog.index') }}"
                       class="sapu-bawah cursor-pointer text-sm font-semibold text-jingga-600 dark:text-jingga-400 transition-colors duration-150 hover:text-jingga-700 dark:hover:text-jingga-300 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                        Lihat katalog
                    </a>
                </div>

                <ol class="border-t border-netral-200 dark:border-arang-600" data-tahap="60">
                    @foreach ($terbaru as $i => $buku)
                        <li class="group relative border-b border-netral-200 dark:border-arang-600" data-muncul>
                            <span class="pointer-events-none absolute inset-y-0 left-0 w-0.5 origin-top scale-y-0 bg-jingga-600 dark:bg-jingga-400 transition-transform duration-300 group-hover:scale-y-100 motion-reduce:transition-none" aria-hidden="true"></span>
                            <a href="{{ route('katalog.show', $buku) }}"
                               class="flex items-center gap-5 py-5 transition-colors duration-200 hover:bg-netral-100/70 dark:hover:bg-arang-700/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-jingga-600 motion-reduce:transition-none sm:gap-8">

                                <span class="w-8 shrink-0 pl-1 font-display text-xl text-netral-400 dark:text-netral-300 transition-colors duration-200 group-hover:text-jingga-600 dark:group-hover:text-jingga-400 sm:w-12 sm:text-2xl">
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                {{-- Buku 3D kecil --}}
                                <span class="hidden w-14 shrink-0 sm:block">
                                    <x-buku-3d :buku="$buku" :punggung="false" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-display text-base font-semibold text-netral-900 dark:text-netral-50 sm:text-lg">
                                        {{ $buku->title }}
                                    </span>
                                    <span class="mt-1 block truncate text-xs text-netral-500 dark:text-netral-400 sm:text-sm">
                                        {{ $buku->author ?: 'Tanpa penulis' }}
                                        &middot; {{ $buku->isUmum() ? 'Umum' : ($buku->prodi?->name ?? 'Umum') }}
                                        @if ($buku->created_at && $buku->created_at->gt(now()->subDays(14)))
                                            &middot; <span class="font-semibold text-jingga-600 dark:text-jingga-400">Baru</span>
                                        @endif
                                    </span>
                                </span>

                                <span class="hidden shrink-0 text-xs text-netral-500 dark:text-netral-400 md:block">
                                    {{ $buku->labelAkses() }}
                                </span>

                                <span class="shrink-0 pr-1 text-netral-400 dark:text-netral-300 transition-all duration-200 group-hover:translate-x-1 group-hover:text-jingga-600 dark:group-hover:text-jingga-400 motion-reduce:transition-none"
                                      aria-hidden="true">
                                    &rarr;
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif
</x-app-layout>