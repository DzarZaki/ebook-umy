<x-app-layout>
    <x-slot name="title">{{ $buku->title }}</x-slot>

    <x-slot name="header">
        <a href="{{ route('katalog.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-semibold text-netral-600 dark:text-netral-400 transition-colors duration-150 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
            <span aria-hidden="true">&larr;</span> Kembali ke katalog
        </a>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            {{-- Kartu Utama Detail Buku --}}
            <div class="grid gap-8 rounded-xl border border-netral-200 dark:border-arang-600 bg-white/90 dark:bg-arang-800/90 p-6 sm:p-10 shadow-sm backdrop-blur-sm transition-colors lg:grid-cols-12">

                {{-- Sampul Buku 3D Realistis --}}
                <div class="flex items-center justify-center p-4 lg:col-span-4 lg:p-6">
                    <div class="w-full max-w-[240px] sm:max-w-[280px]">
                        <x-buku-3d :buku="$buku" />
                    </div>
                </div>

                {{-- Keterangan & Spesifikasi --}}
                <div class="flex flex-col lg:col-span-8">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($buku->isUmum())
                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-netral-50 dark:bg-arang-700 px-2.5 py-0.5 text-xs font-medium text-netral-700 dark:text-netral-300 badge-category">Umum</span>
                        @else
                            <span class="rounded border border-jingga-200 dark:border-jingga-700/50 bg-jingga-50 dark:bg-jingga-900/30 px-2.5 py-0.5 text-xs font-medium text-jingga-700 dark:text-jingga-300 badge-category">{{ $buku->prodi?->name ?? 'Umum' }}</span>
                        @endif

                        @if ($buku->category)
                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2.5 py-0.5 text-xs font-medium text-netral-600 dark:text-netral-400 badge-category">{{ $buku->category->name }}</span>
                        @endif

                        @unless ($buku->is_published)
                            <span class="rounded border border-red-200 dark:border-red-700/50 bg-red-50 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Draf</span>
                        @endunless
                    </div>

                    <h1 class="mt-4 font-display text-2xl font-semibold leading-tight text-netral-900 dark:text-netral-50 sm:text-3xl">
                        {{ $buku->title }}
                    </h1>

                    <p class="mt-2 text-sm font-medium text-netral-500 dark:text-netral-400">
                        {{ $buku->author ?: 'Tanpa penulis' }}
                    </p>

                    @if ($buku->description)
                        <div class="mt-5 text-sm leading-relaxed text-netral-600 dark:text-netral-300">
                            {{ $buku->description }}
                        </div>
                    @endif

                    {{-- Lembar Spesifikasi Berkas --}}
                    <dl class="mt-6 grid gap-px overflow-hidden rounded-lg border border-netral-200 dark:border-arang-600 bg-netral-200 dark:border-arang-600 bg-netral-200 dark:bg-arang-600 text-sm sm:grid-cols-2">
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs font-medium uppercase tracking-wider text-netral-500 dark:text-netral-400">Aturan unduh</dt>
                            <dd class="mt-1 font-semibold text-netral-900 dark:text-netral-100">{{ $buku->labelAkses() }}</dd>
                        </div>
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs font-medium uppercase tracking-wider text-netral-500 dark:text-netral-400">Ukuran berkas</dt>
                            <dd class="mt-1 font-semibold text-netral-900 dark:text-netral-100">{{ $buku->ukuranMb() }} MB</dd>
                        </div>
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs font-medium uppercase tracking-wider text-netral-500 dark:text-netral-400">Jumlah halaman</dt>
                            <dd class="mt-1 font-semibold text-netral-900 dark:text-netral-100">{{ $buku->page_count ? $buku->page_count.' halaman' : 'Tidak dicantumkan' }}</dd>
                        </div>
                        <div class="bg-white dark:bg-arang-700/80 px-4 py-3">
                            <dt class="text-xs font-medium uppercase tracking-wider text-netral-500 dark:text-netral-400">Diunggah oleh</dt>
                            <dd class="mt-1 font-semibold text-netral-900 dark:text-netral-100">{{ $buku->pengunggah?->name ?? '—' }}</dd>
                        </div>
                    </dl>

                    @php($aturan = $buku->aturanUnduhUntuk(auth()->user()))

                    {{-- Tombol Aksi --}}
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="{{ route('katalog.baca', $buku) }}"
                           class="inline-flex cursor-pointer items-center justify-center rounded bg-jingga-600 dark:bg-jingga-500 px-6 py-2.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 btn-press shadow-sm">
                            Baca Buku
                        </a>

                        {{-- Tombol Simpan --}}
                        <x-tombol-simpan :buku="$buku"
                                         :tersimpan="auth()->user()->telahMenyimpan($buku)" />

                        <span class="text-xs text-netral-500 dark:text-netral-400">
                            {{ $aturan['alasan'] }}
                        </span>
                    </div>

                    @if ($buku->watermark_enabled)
                        <div class="mt-5 rounded-lg border border-netral-200 dark:border-arang-600 bg-netral-50/80 dark:bg-arang-700/40 p-3.5 text-xs leading-relaxed text-netral-600 dark:text-netral-300">
                            <span class="font-semibold text-netral-800 dark:text-netral-200">Perlindungan hak cipta:</span> Berkas yang Anda unduh akan mencantumkan nama dan email Anda sebagai stempel identitas pembaca.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Bacaan lain yang serupa --}}
            @if ($serupa->isNotEmpty())
                <div class="mt-12">
                    <h2 class="font-display text-xl font-semibold text-netral-900 dark:text-netral-50">Bacaan lain yang mungkin cocok</h2>

                    <ul class="mt-4 divide-y divide-netral-200 dark:divide-arang-600 overflow-hidden rounded-xl border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/80 backdrop-blur-sm shadow-sm">
                        @foreach ($serupa as $lain)
                            <li class="flex items-center justify-between gap-4 px-6 py-4 list-item-modern">
                                <div class="min-w-0">
                                    <a href="{{ route('katalog.show', $lain) }}"
                                       class="cursor-pointer font-display text-base font-semibold text-netral-900 dark:text-netral-50 transition-colors duration-150 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                                        {{ $lain->title }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-netral-500 dark:text-netral-400">
                                        {{ $lain->author ?: 'Tanpa penulis' }} &middot; {{ $lain->labelAkses() }}
                                    </p>
                                </div>
                                <span class="shrink-0 rounded border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700 px-2 py-0.5 text-xs font-medium text-netral-600 dark:text-netral-300">
                                    {{ $lain->prodi?->name ?? 'Umum' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>