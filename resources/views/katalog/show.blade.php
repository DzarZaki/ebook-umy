<x-app-layout>
    {{-- Judul tab akhirnya menyebut nama bukunya, memakai slot yang kita
         pasang di layout pada Langkah 68. Ini halaman pertama yang memakainya. --}}
    <x-slot name="title">{{ $buku->title }}</x-slot>

    <x-slot name="header">
        <a href="{{ route('katalog.index') }}"
           class="cursor-pointer text-sm font-medium text-netral-400 underline underline-offset-4 transition-colors duration-150 hover:text-netral-100 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
            &larr; Kembali ke katalog
        </a>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <div class="grid gap-8 rounded-lg border border-arang-600 p-6 sm:p-8 lg:grid-cols-12 glass-card hover-lift">

                {{-- Sampul --}}
                {{-- Sengaja TANPA aspect-[3/4] dan tanpa pemotongan: di halaman
                     ini sampul adalah pokok bahasannya, bukan sekadar penanda
                     pengenal, jadi ia ditampilkan dengan proporsi aslinya. --}}
                <div class="lg:col-span-4">
                    @if ($buku->coverUrl())
                        <img src="{{ $buku->coverUrl() }}" alt="Sampul {{ $buku->title }}"
                             class="w-full rounded border border-netral-200 dark:border-arang-600 shadow-sm dark:shadow-none">
                    @else
                        <span class="flex aspect-[3/4] w-full items-center justify-center rounded border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 font-display text-5xl font-semibold text-netral-700 dark:text-netral-100">
                            {{ $buku->inisial() }}
                        </span>
                    @endif
                </div>

                {{-- Keterangan --}}
                <div class="lg:col-span-8">
                    <div class="flex flex-wrap gap-2">
                        @if ($buku->isUmum())
                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-700 dark:text-netral-300 badge-category">Umum</span>
                        @else
                            <span class="rounded border border-jingga-200 dark:border-jingga-700/50 bg-jingga-50 dark:bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-700 dark:text-jingga-300 badge-category">{{ $buku->prodi?->name ?? 'Umum' }}</span>
                        @endif

                        @if ($buku->category)
                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-600 dark:text-netral-400 badge-category">{{ $buku->category->name }}</span>
                        @endif

                        @unless ($buku->is_published)
                            <span class="rounded border border-red-200 dark:border-red-700/50 bg-red-50 dark:bg-red-900/30 px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300 badge-category">Draf</span>
                        @endunless
                    </div>

                    <h1 class="mt-3 font-display text-3xl font-semibold leading-tight text-netral-900 dark:text-netral-50">{{ $buku->title }}</h1>
                    <p class="mt-2 text-sm text-netral-500 dark:text-netral-400">{{ $buku->author ?: 'Tanpa penulis' }}</p>

                    @if ($buku->description)
                        <p class="mt-5 text-sm leading-relaxed text-netral-600 dark:text-netral-300">{{ $buku->description }}</p>
                    @endif

                    <dl class="mt-6 grid gap-px overflow-hidden rounded-lg border border-netral-200 dark:border-arang-600 bg-netral-200 dark:bg-arang-600 text-sm sm:grid-cols-2">
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs text-netral-500 dark:text-netral-400">Aturan unduh</dt>
                            <dd class="mt-0.5 font-medium text-netral-900 dark:text-netral-100">{{ $buku->labelAkses() }}</dd>
                        </div>
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs text-netral-500 dark:text-netral-400">Ukuran berkas</dt>
                            <dd class="mt-0.5 font-medium text-netral-900 dark:text-netral-100">{{ $buku->ukuranMb() }} MB</dd>
                        </div>
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs text-netral-500 dark:text-netral-400">Jumlah halaman</dt>
                            <dd class="mt-0.5 font-medium text-netral-900 dark:text-netral-100">{{ $buku->page_count ?? 'Tidak dicantumkan' }}</dd>
                        </div>
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs text-netral-500 dark:text-netral-400">Diunggah oleh</dt>
                            <dd class="mt-0.5 font-medium text-netral-900 dark:text-netral-100">{{ $buku->pengunggah?->name ?? '—' }}</dd>
                        </div>
                    </dl>

                    @php($aturan = $buku->aturanUnduhUntuk(auth()->user()))

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ route('katalog.baca', $buku) }}"
                           class="cursor-pointer rounded bg-jingga-600 dark:bg-jingga-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 hover-glow btn-press shadow-sm">
                            Baca Buku
                        </a>

                        {{-- Tombol simpan bergaya penuh --}}
                        <x-tombol-simpan :buku="$buku"
                                         :tersimpan="auth()->user()->telahMenyimpan($buku)" />

                        <span class="text-xs text-netral-500 dark:text-netral-400">{{ $aturan['alasan'] }}</span>
                    </div>

                    @if ($buku->watermark_enabled)
                        <p class="mt-4 rounded border border-netral-200 dark:border-arang-600 bg-white/70 dark:bg-arang-700/50 p-3 text-xs text-netral-500 dark:text-netral-400">
                            Berkas yang Anda unduh akan mencantumkan nama dan email Anda sebagai penanda kepemilikan.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Bacaan lain --}}
            @if ($serupa->isNotEmpty())
                <h2 class="mt-10 font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Bacaan lain yang mungkin cocok</h2>

                <ul class="mt-3 divide-y divide-netral-200 dark:divide-arang-600 overflow-hidden rounded-lg border border-netral-200 dark:border-arang-600 bg-white/60 dark:bg-arang-700/30">
                    @foreach ($serupa as $lain)
                        <li class="flex items-center justify-between gap-4 px-5 py-4 list-item-modern">
                            <div class="min-w-0">
                                <a href="{{ route('katalog.show', $lain) }}"
                                   class="cursor-pointer font-display text-sm font-semibold text-netral-900 dark:text-netral-50 transition-colors duration-150 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                                    {{ $lain->title }}
                                </a>
                                <p class="text-xs text-netral-500 dark:text-netral-400">{{ $lain->author ?: 'Tanpa penulis' }} &middot; {{ $lain->labelAkses() }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-netral-500 dark:text-netral-400">{{ $lain->prodi?->name ?? 'Umum' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>