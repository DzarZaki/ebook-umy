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
    <section class="tekstur-kertas border-b border-sepia-700 bg-sepia-900">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-8" data-muncul>
                <div class="max-w-xl">
                    <p class="text-label font-semibold uppercase text-jingga-700">Rak pribadi</p>

                    <h1 class="judul-raksasa mt-3 text-besar text-kabut-50">
                        Koleksi Saya
                    </h1>

                    <p class="mt-4 text-sm leading-relaxed text-kabut-400">
                        Buku yang Anda simpan sendiri, beserta halaman-halaman
                        yang Anda tandai saat membaca.
                    </p>
                </div>

                <dl class="flex gap-8 lg:gap-10">
                    <div>
                        <dd class="font-display text-4xl font-semibold leading-none text-kabut-50 sm:text-5xl">
                            {{ str_pad($tersimpan->total(), 2, '0', STR_PAD_LEFT) }}
                        </dd>
                        <dt class="mt-2 text-label font-semibold uppercase text-kabut-500">Buku<br>tersimpan</dt>
                    </div>
                    <div class="border-l border-sepia-700 pl-8 lg:pl-10">
                        <dd class="font-display text-4xl font-semibold leading-none text-kabut-50 sm:text-5xl">
                            {{ str_pad($jumlahHalamanDitandai, 2, '0', STR_PAD_LEFT) }}
                        </dd>
                        <dt class="mt-2 text-label font-semibold uppercase text-kabut-500">Halaman<br>ditandai</dt>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         2. TAB BERNOMOR
         Tetap tautan biasa, bukan JavaScript: bisa ditandai sebagai favorit,
         bisa dibuka di tab baru, dan tetap bekerja bila skrip gagal dimuat.
         Bentuknya saja yang berubah — nomor di atas, nama di bawah.
         ===================================================================== --}}
    <nav class="border-b border-sepia-700 bg-sepia-800/30" aria-label="Bagian koleksi">
        <div class="mx-auto flex max-w-7xl gap-8 px-4 sm:gap-12 sm:px-6 lg:px-8">
            <a href="{{ route('koleksi.index') }}"
               @if ($tab === 'tersimpan') aria-current="page" @endif
               @class([
                   'group -mb-px cursor-pointer border-b-2 py-5 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600',
                   'border-jingga-600' => $tab === 'tersimpan',
                   'border-transparent hover:border-sepia-600' => $tab !== 'tersimpan',
               ])>
                <span @class([
                    'block font-display text-lg transition-colors duration-150 motion-reduce:transition-none',
                    'text-jingga-600' => $tab === 'tersimpan',
                    'text-kabut-300 group-hover:text-kabut-500' => $tab !== 'tersimpan',
                ])>01</span>
                <span @class([
                    'mt-1 block text-sm font-semibold transition-colors duration-150 motion-reduce:transition-none',
                    'text-kabut-50' => $tab === 'tersimpan',
                    'text-kabut-500 group-hover:text-kabut-300' => $tab !== 'tersimpan',
                ])>
                    Tersimpan
                    <span class="font-normal text-kabut-400">({{ $tersimpan->total() }})</span>
                </span>
            </a>

            <a href="{{ route('koleksi.index', ['tab' => 'penanda']) }}"
               @if ($tab === 'penanda') aria-current="page" @endif
               @class([
                   'group -mb-px cursor-pointer border-b-2 py-5 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600',
                   'border-jingga-600' => $tab === 'penanda',
                   'border-transparent hover:border-sepia-600' => $tab !== 'penanda',
               ])>
                <span @class([
                    'block font-display text-lg transition-colors duration-150 motion-reduce:transition-none',
                    'text-jingga-600' => $tab === 'penanda',
                    'text-kabut-300 group-hover:text-kabut-500' => $tab !== 'penanda',
                ])>02</span>
                <span @class([
                    'mt-1 block text-sm font-semibold transition-colors duration-150 motion-reduce:transition-none',
                    'text-kabut-50' => $tab === 'penanda',
                    'text-kabut-500 group-hover:text-kabut-300' => $tab !== 'penanda',
                ])>
                    Penanda halaman
                    <span class="font-normal text-kabut-400">({{ $jumlahBukuBerpenanda }})</span>
                </span>
            </a>
        </div>
    </nav>

    <section class="bg-sepia-800/10 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Pesan hasil menyimpan/melepas buku. --}}
            @if (session('status'))
                <div class="mb-10 border-l-2 border-jingga-500 bg-jingga-900/30 px-4 py-3 text-sm text-jingga-300"
                     role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($tab === 'tersimpan')

                @if ($tersimpan->isEmpty())
                    <div class="mx-auto max-w-md py-16 text-center" data-muncul>
                        <p class="text-besar text-kabut-50">Rak ini masih kosong</p>
                        <p class="mt-4 text-sm leading-relaxed text-kabut-400">
                            Tekan pita di sudut buku mana pun untuk menyimpannya ke sini,
                            supaya mudah ditemukan lagi nanti.
                        </p>
                        <a href="{{ route('katalog.index') }}"
                           class="mt-8 inline-flex cursor-pointer items-center rounded bg-jingga-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
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

                                <p class="mt-5 line-clamp-2 text-sm font-semibold leading-snug text-kabut-50">
                                    {{ $buku->title }}
                                </p>

                                <p class="mt-1 truncate text-xs text-kabut-500">
                                    {{ $buku->author ?: 'Tanpa penulis' }}
                                </p>

                                {{-- Pita di luar tautan di atas: <form> di dalam
                                     <a> adalah HTML tidak sah. --}}
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <span class="truncate text-[11px] font-medium uppercase tracking-wider text-kabut-400">
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
                        <p class="text-besar text-kabut-50">Belum ada halaman ditandai</p>
                        <p class="mt-4 text-sm leading-relaxed text-kabut-400">
                            Saat membaca, tekan tombol penanda di pembaca untuk menyimpan
                            halaman penting. Semuanya akan berkumpul di sini.
                        </p>
                    </div>
                @else
                    {{--
                        Penanda tidak berbentuk kartu, dan itu keputusan lama
                        yang saya pertahankan: satu buku bisa punya belasan
                        halaman ditandai, dan keping halaman sebanyak itu akan
                        membungkus jadi tumpukan tak terbaca di dalam kartu
                        selebar 250 px. Bentuk mengikuti isi.
                    --}}
                    <ol class="border-t border-sepia-700" data-tahap="60">
                        @foreach ($bukuBerpenanda as $i => $baris)
                            @php($buku = $baris['buku'])

                            <li class="border-b border-sepia-700 py-8" data-muncul>
                                <div class="flex items-start gap-5 sm:gap-8">

                                    <span class="w-7 shrink-0 pt-1 font-display text-lg text-kabut-300 sm:w-12 sm:text-2xl">
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
                                                <h2 class="text-lg font-semibold leading-snug text-kabut-50 sm:text-xl">
                                                    <a href="{{ route('katalog.show', $buku) }}"
                                                       class="sapu-bawah cursor-pointer focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2">
                                                        {{ $buku->title }}
                                                    </a>
                                                </h2>

                                                <p class="mt-1.5 text-xs text-kabut-500 sm:text-sm">
                                                    {{ $buku->author ?: 'Tanpa penulis' }}
                                                    &middot; {{ count($baris['halaman']) }} halaman ditandai
                                                </p>
                                            </div>

                                            <x-tombol-simpan :buku="$buku" class="shrink-0" />
                                        </div>

                                        {{-- Keping halaman: nomornya dicetak besar,
                                             karena nomor itulah yang dicari mata. --}}
                                        <div class="mt-5 flex flex-wrap gap-2">
                                            @foreach ($baris['halaman'] as $halaman)
                                                <a href="{{ route('katalog.baca', ['buku' => $buku, 'halaman' => $halaman]) }}"
                                                   class="group cursor-pointer border border-sepia-600 bg-sepia-800 px-3 py-1.5 text-xs font-medium text-kabut-300 transition-colors duration-150 hover:border-jingga-500 hover:bg-jingga-900/30 hover:text-jingga-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-sepia-900 motion-reduce:transition-none">
                                                    Hal. <span class="font-display text-sm font-semibold text-kabut-100 transition-colors duration-150 group-hover:text-jingga-400 motion-reduce:transition-none">{{ $halaman }}</span>
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