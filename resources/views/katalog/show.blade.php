<x-app-layout>
    {{-- Judul tab akhirnya menyebut nama bukunya, memakai slot yang kita
         pasang di layout pada Langkah 68. Ini halaman pertama yang memakainya. --}}
    <x-slot name="title">{{ $buku->title }}</x-slot>

    <x-slot name="header">
        <a href="{{ route('katalog.index') }}"
           class="cursor-pointer text-sm font-medium text-kabut-400 underline underline-offset-4 transition-colors duration-150 hover:text-kabut-100 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
            &larr; Kembali ke katalog
        </a>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <div class="grid gap-8 rounded-lg border border-sepia-700 bg-sepia-800/50 p-6 sm:p-8 lg:grid-cols-12">

                {{-- Sampul --}}
                {{-- Sengaja TANPA aspect-[3/4] dan tanpa pemotongan: di halaman
                     ini sampul adalah pokok bahasannya, bukan sekadar penanda
                     pengenal, jadi ia ditampilkan dengan proporsi aslinya. --}}
                <div class="lg:col-span-4">
                    @if ($buku->coverUrl())
                        <img src="{{ $buku->coverUrl() }}" alt="Sampul {{ $buku->title }}"
                             class="w-full rounded border border-sepia-700">
                    @else
                        <span class="flex aspect-[3/4] w-full items-center justify-center rounded border border-sepia-700 bg-sepia-800 font-display text-5xl font-semibold text-kabut-100">
                            {{ $buku->inisial() }}
                        </span>
                    @endif
                </div>

                {{-- Keterangan --}}
                <div class="lg:col-span-8">
                    <div class="flex flex-wrap gap-2">
                        @if ($buku->isUmum())
                            <span class="rounded border border-sepia-600 bg-sepia-800 px-2 py-1 text-xs font-medium text-kabut-300">Umum</span>
                        @else
                            {{-- prodi?->name : buku yang prodi_id-nya kosong tetapi
                                 tidak tertandai umum tidak boleh mematikan halaman. --}}
                            <span class="rounded border border-jingga-700/50 bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-300">{{ $buku->prodi?->name ?? 'Umum' }}</span>
                        @endif

                        @if ($buku->category)
                            <span class="rounded border border-sepia-600 bg-sepia-800 px-2 py-1 text-xs font-medium text-kabut-400">{{ $buku->category->name }}</span>
                        @endif

                        @unless ($buku->is_published)
                            <span class="rounded border border-red-700/50 bg-red-900/30 px-2 py-1 text-xs font-medium text-red-300">Draf</span>
                        @endunless
                    </div>

                    <h1 class="mt-3 font-display text-3xl font-semibold leading-tight text-kabut-50">{{ $buku->title }}</h1>
                    <p class="mt-2 text-sm text-kabut-400">{{ $buku->author ?: 'Tanpa penulis' }}</p>

                    @if ($buku->description)
                        <p class="mt-5 text-sm leading-relaxed text-kabut-300">{{ $buku->description }}</p>
                    @endif

                    <dl class="mt-6 grid gap-px overflow-hidden rounded-lg border border-sepia-700 bg-sepia-700 text-sm sm:grid-cols-2">
                        <div class="bg-sepia-800/80 px-4 py-3">
                            <dt class="text-xs text-kabut-400">Aturan unduh</dt>
                            <dd class="mt-0.5 font-medium text-kabut-100">{{ $buku->labelAkses() }}</dd>
                        </div>
                        <div class="bg-sepia-800/80 px-4 py-3">
                            <dt class="text-xs text-kabut-400">Ukuran berkas</dt>
                            <dd class="mt-0.5 font-medium text-kabut-100">{{ $buku->ukuranMb() }} MB</dd>
                        </div>
                        <div class="bg-sepia-800/80 px-4 py-3">
                            <dt class="text-xs text-kabut-400">Jumlah halaman</dt>
                            <dd class="mt-0.5 font-medium text-kabut-100">{{ $buku->page_count ?? 'Tidak dicantumkan' }}</dd>
                        </div>
                        <div class="bg-sepia-800/80 px-4 py-3">
                            <dt class="text-xs text-kabut-400">Diunggah oleh</dt>
                            <dd class="mt-0.5 font-medium text-kabut-100">{{ $buku->pengunggah?->name ?? '—' }}</dd>
                        </div>
                    </dl>

                    @php($aturan = $buku->aturanUnduhUntuk(auth()->user()))

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ route('katalog.baca', $buku) }}"
                           class="cursor-pointer rounded bg-jingga-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            Baca Buku
                        </a>

                        {{-- Tombol simpan bergaya penuh. Inilah tempat yang
                             dituju komponen itu sejak awal, tetapi belum pernah
                             dipasang di sini. --}}
                        <x-tombol-simpan :buku="$buku"
                                         :tersimpan="auth()->user()->telahMenyimpan($buku)" />

                        <span class="text-xs text-kabut-400">{{ $aturan['alasan'] }}</span>
                    </div>

                    @if ($buku->watermark_enabled)
                        <p class="mt-4 rounded border border-sepia-700 bg-sepia-800/50 p-3 text-xs text-kabut-400">
                            Berkas yang Anda unduh akan mencantumkan nama dan email Anda sebagai penanda kepemilikan.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Bacaan lain --}}
            @if ($serupa->isNotEmpty())
                <h2 class="mt-10 font-display text-lg font-semibold text-kabut-50">Bacaan lain yang mungkin cocok</h2>

                <ul class="mt-3 divide-y divide-sepia-700 overflow-hidden rounded-lg border border-sepia-700 bg-sepia-800/30">
                    @foreach ($serupa as $lain)
                        <li class="flex items-center justify-between gap-4 px-5 py-4 transition-colors duration-200 hover:bg-sepia-800/40 motion-reduce:transition-none">
                            <div class="min-w-0">
                                <a href="{{ route('katalog.show', $lain) }}"
                                   class="cursor-pointer font-display text-sm font-semibold text-kabut-50 transition-colors duration-150 hover:text-jingga-400 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                                    {{ $lain->title }}
                                </a>
                                <p class="text-xs text-kabut-400">{{ $lain->author ?: 'Tanpa penulis' }} &middot; {{ $lain->labelAkses() }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-kabut-400">{{ $lain->prodi?->name ?? 'Umum' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>