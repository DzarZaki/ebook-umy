<x-app-layout>
    <x-slot name="title">Koleksi Saya</x-slot>

    @php
        // Dua angka yang berbeda maknanya: berapa BUKU yang punya penanda,
        // dan berapa HALAMAN yang ditandai seluruhnya. Angka kedua inilah
        // yang membuat rak ini terasa milik Anda sendiri.
        $jumlahBukuBerpenanda = $bukuBerpenanda->count();
        $jumlahHalamanDitandai = $bukuBerpenanda->sum(fn ($baris) => count($baris['halaman']));
    @endphp

    {{-- =====================================================================
         1. KEPALA
         ===================================================================== --}}
    <section class="tekstur-kertas border-b border-netral-200 dark:border-arang-600 bg-white/60 dark:bg-arang-800 transition-colors">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-8" data-muncul>
                <div class="max-w-xl">
                    <p class="text-label font-semibold uppercase text-jingga-600 dark:text-jingga-400">Rak pribadi</p>

                    <h1 class="judul-raksasa mt-3 text-besar text-netral-900 dark:text-netral-50">
                        Koleksi Saya
                    </h1>

                    <p class="mt-4 text-sm leading-relaxed text-netral-600 dark:text-netral-400">
                        Buku yang Anda simpan sendiri, beserta halaman-halaman
                        yang Anda tandai saat membaca.
                    </p>
                </div>

                {{-- dt lebih dulu di DOM agar screen reader membaca label
                    sebelum angkanya; flex-col-reverse menjaga tampilan
                    angka-besar-di-atas seperti semula. --}}
                <dl class="flex gap-8 lg:gap-10">
                    <div class="flex flex-col-reverse">
                        <dt class="mt-2 text-label font-semibold uppercase text-netral-500">Buku<br>tersimpan</dt>
                        <dd class="font-display text-4xl font-semibold leading-none text-netral-900 dark:text-netral-50 sm:text-5xl">
                            {{ str_pad($tersimpan->total(), 2, '0', STR_PAD_LEFT) }}
                        </dd>
                    </div>
                    <div class="flex flex-col-reverse border-l border-netral-200 dark:border-arang-600 pl-8 lg:pl-10">
                        <dt class="mt-2 text-label font-semibold uppercase text-netral-500">Halaman<br>ditandai</dt>
                        <dd class="font-display text-4xl font-semibold leading-none text-netral-900 dark:text-netral-50 sm:text-5xl">
                            {{ str_pad($jumlahHalamanDitandai, 2, '0', STR_PAD_LEFT) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         2. TAB BERNOMOR
         ===================================================================== --}}
    <nav class="border-b border-netral-200 dark:border-arang-600 bg-white/40 dark:bg-arang-700/30 transition-colors" aria-label="Bagian koleksi">
        <div class="mx-auto flex max-w-7xl gap-8 px-4 sm:gap-12 sm:px-6 lg:px-8">
            <a href="{{ route('koleksi.index') }}"
               @if ($tab === 'tersimpan') aria-current="page" @endif
               @class([
                   'group -mb-px cursor-pointer border-b-2 py-4 sm:py-5 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600',
                   'border-jingga-600 dark:border-jingga-400' => $tab === 'tersimpan',
                   'border-transparent hover:border-netral-300 dark:hover:border-arang-500' => $tab !== 'tersimpan',
               ])>
                <span @class([
                    'block font-display text-base sm:text-lg transition-colors duration-150 motion-reduce:transition-none',
                    'text-jingga-600 dark:text-jingga-400' => $tab === 'tersimpan',
                    'text-netral-400 dark:text-netral-400 group-hover:text-netral-600 dark:group-hover:text-netral-100' => $tab !== 'tersimpan',
                ])>01</span>
                <span @class([
                    'mt-1 block text-sm font-semibold transition-colors duration-150 motion-reduce:transition-none',
                    'text-netral-900 dark:text-netral-50' => $tab === 'tersimpan',
                    'text-netral-500 dark:text-netral-400 group-hover:text-netral-700 dark:group-hover:text-netral-200' => $tab !== 'tersimpan',
                ])>
                    Tersimpan
                    <span class="font-normal text-netral-400 dark:text-netral-400">({{ $tersimpan->total() }})</span>
                </span>
            </a>

            <a href="{{ route('koleksi.index', ['tab' => 'penanda']) }}"
               @if ($tab === 'penanda') aria-current="page" @endif
               @class([
                   'group -mb-px cursor-pointer border-b-2 py-4 sm:py-5 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600',
                   'border-jingga-600 dark:border-jingga-400' => $tab === 'penanda',
                   'border-transparent hover:border-netral-300 dark:hover:border-arang-500' => $tab !== 'penanda',
               ])>
                <span @class([
                    'block font-display text-base sm:text-lg transition-colors duration-150 motion-reduce:transition-none',
                    'text-jingga-600 dark:text-jingga-400' => $tab === 'penanda',
                    'text-netral-400 dark:text-netral-400 group-hover:text-netral-600 dark:group-hover:text-netral-100' => $tab !== 'penanda',
                ])>02</span>
                <span @class([
                    'mt-1 block text-sm font-semibold transition-colors duration-150 motion-reduce:transition-none',
                    'text-netral-900 dark:text-netral-50' => $tab === 'penanda',
                    'text-netral-500 dark:text-netral-400 group-hover:text-netral-700 dark:group-hover:text-netral-200' => $tab !== 'penanda',
                ])>
                    Penanda halaman
                    <span class="font-normal text-netral-400 dark:text-netral-400">({{ $jumlahBukuBerpenanda }})</span>
                </span>
            </a>
        </div>
    </nav>

    <section class="bg-transparent py-10 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Pesan hasil menyimpan/melepas buku. --}}
            @if (session('status'))
                <div class="mb-8 rounded-lg border border-jingga-200 dark:border-jingga-700/50 bg-jingga-50/90 dark:bg-jingga-900/30 px-4 py-3 text-sm font-medium text-jingga-800 dark:text-jingga-300 shadow-sm"
                     role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($tab === 'tersimpan')

                @if ($tersimpan->isEmpty())
                    <div class="mx-auto max-w-md py-16 text-center" data-muncul>
                        <p class="text-besar text-netral-900 dark:text-netral-50">Rak ini masih kosong</p>
                        <p class="mt-4 text-sm leading-relaxed text-netral-600 dark:text-netral-400">
                            Tekan pita di sudut buku mana pun untuk menyimpannya ke sini,
                            supaya mudah ditemukan lagi nanti.
                        </p>
                        <a href="{{ route('katalog.index') }}"
                           class="mt-8 inline-flex cursor-pointer items-center rounded bg-jingga-600 dark:bg-jingga-500 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none shadow-sm">
                            Jelajahi katalog
                        </a>
                    </div>
                @else
                    {{--
                        Lemari sungguhan: buku berdiri berjajar, punggungnya
                        terlihat, tebalnya berbeda-beda menurut jumlah halaman.

                        gap-y-14 bukan kemewahan. Setiap buku punya bayangan
                        yang menyebar ke bawah; dengan jarak sempit, bayangan
                        satu buku akan jatuh ke atas judul buku di baris
                        berikutnya.

                        Catatan jujur: saya TIDAK menggambar papan rak berupa
                        garis mendatar di bawah setiap baris. Tinggi setiap
                        baris berbeda karena judul bisa satu atau dua baris,
                        jadi papan itu akan melayang tidak sejajar dengan
                        buku-bukunya. Rak palsu yang tidak lurus lebih buruk
                        daripada tidak ada rak.
                    --}}
                    <div class="grid grid-cols-2 gap-x-6 gap-y-14 xs:grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
                         data-tahap="55">
                        @foreach ($tersimpan as $buku)
                            <div data-muncul>
                                <a href="{{ route('katalog.show', $buku) }}"
                                   class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-4">
                                    <x-buku-3d :buku="$buku" />
                                </a>

                                <p class="mt-5 line-clamp-2 text-sm font-semibold leading-snug text-netral-900 dark:text-netral-50">
                                    {{ $buku->title }}
                                </p>

                                <p class="mt-1 truncate text-xs text-netral-500 dark:text-netral-400">
                                    {{ $buku->author ?: 'Tanpa penulis' }}
                                </p>

                                {{-- Pita di luar tautan di atas: <form> di dalam
                                     <a> adalah HTML tidak sah. --}}
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <span class="truncate text-[11px] font-medium uppercase tracking-wider text-netral-500 dark:text-netral-400">
                                        {{ $buku->isUmum() ? 'Umum' : ($buku->prodi?->name ?? 'Umum') }}
                                    </span>

                                    <x-tombol-simpan :buku="$buku"
                                                     :tersimpan="true"
                                                     gaya="ikon"
                                                     class="shrink-0" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-14">{{ $tersimpan->links() }}</div>
                @endif

            @else

                @if ($bukuBerpenanda->isEmpty())
                    <div class="mx-auto max-w-md py-16 text-center" data-muncul>
                        <p class="text-besar text-netral-900 dark:text-netral-50">Belum ada halaman ditandai</p>
                        <p class="mt-4 text-sm leading-relaxed text-netral-600 dark:text-netral-400">
                            Saat membaca, tekan tombol penanda di pembaca untuk menyimpan
                            halaman penting. Semuanya akan berkumpul di sini.
                        </p>
                    </div>
                @else
                    <ol class="border-t border-netral-200 dark:border-arang-600" data-tahap="60">
                        @foreach ($bukuBerpenanda as $i => $baris)
                            @php($buku = $baris['buku'])

                            <li class="border-b border-netral-200 dark:border-arang-600 py-8" data-muncul>
                                <div class="flex items-start gap-5 sm:gap-8">

                                    <span class="w-7 shrink-0 pt-1 font-display text-lg text-netral-400 dark:text-netral-300 sm:w-12 sm:text-2xl">
                                        {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                    </span>

                                    <a href="{{ route('katalog.show', $buku) }}"
                                       tabindex="-1"
                                       aria-hidden="true"
                                       class="hidden w-16 shrink-0 xs:block sm:w-20">
                                        <x-buku-3d :buku="$buku" :punggung="false" />
                                    </a>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h2 class="text-lg font-semibold leading-snug text-netral-900 dark:text-netral-50 sm:text-xl">
                                                    <a href="{{ route('katalog.show', $buku) }}"
                                                       class="sapu-bawah cursor-pointer focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2">
                                                        {{ $buku->title }}
                                                    </a>
                                                </h2>

                                                <p class="mt-1.5 text-xs text-netral-500 dark:text-netral-400 sm:text-sm">
                                                    {{ $buku->author ?: 'Tanpa penulis' }}
                                                    &middot; {{ count($baris['halaman']) }} halaman ditandai
                                                </p>
                                            </div>

                                            <x-tombol-simpan :buku="$buku" class="shrink-0" />
                                        </div>

                                        {{-- Keping halaman --}}
                                        <div class="mt-5 flex flex-wrap gap-2">
                                            @foreach ($baris['halaman'] as $halaman)
                                                <a href="{{ route('katalog.baca', ['buku' => $buku, 'halaman' => $halaman]) }}"
                                                   class="group cursor-pointer rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-3 py-1.5 text-xs font-medium text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-jingga-500 hover:bg-jingga-50 dark:hover:bg-jingga-900/30 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-netral-100 dark:focus-visible:ring-offset-arang-800 motion-reduce:transition-none shadow-sm">
                                                    Hal. <span class="font-display text-sm font-semibold text-netral-900 dark:text-netral-100 transition-colors duration-150 group-hover:text-jingga-600 dark:group-hover:text-jingga-400 motion-reduce:transition-none">{{ $halaman }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif

            @endif
        </div>
    </section>
</x-app-layout>