<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('katalog.show', $buku) }}" class="text-xs font-medium text-kabut-500 underline underline-offset-4 hover:text-kabut-800">
                    &larr; Kembali ke detail buku
                </a>
                <h2 class="mt-1 truncate font-display text-lg font-semibold leading-tight text-kabut-800">{{ $buku->title }}</h2>
            </div>
            <span class="rounded-sm bg-kabut-100 px-2 py-1 text-xs font-medium text-kabut-600">{{ $buku->labelAkses() }}</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            {{-- Bilah kendali --}}
            <div class="sticky top-0 z-10 flex flex-wrap items-center justify-between gap-3 border border-kabut-200 bg-white px-4 py-3">
                <div class="flex items-center gap-2">
                    <button id="tombol-sebelum" type="button"
                            class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">
                        &larr; Mundur
                    </button>

                    <div class="flex items-center gap-1.5 text-sm text-kabut-600">
                        <input id="isian-halaman" type="number" min="1" value="1"
                               class="w-16 rounded-sm border-kabut-300 py-1 text-center text-sm focus:border-jingga-500 focus:ring-jingga-500">
                        <span>dari <span id="total-halaman">…</span></span>
                    </div>

                    <button id="tombol-sesudah" type="button"
                            class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">
                        Maju &rarr;
                    </button>

                    <button id="tombol-penanda" type="button" aria-pressed="false"
                            aria-label="Tandai halaman ini" title="Tandai halaman ini"
                            class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">
                        <svg id="ikon-penanda-outline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 3.75H6.75A2.25 2.25 0 0 0 4.5 6v14.25l7.5-4.5 7.5 4.5V6a2.25 2.25 0 0 0-2.25-2.25Z"/>
                        </svg>
                        <svg id="ikon-penanda-isi" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                             fill="currentColor" class="hidden h-4 w-4">
                            <path d="M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v14.25a.75.75 0 0 1-1.136.643L12 17.079l-6.364 3.814A.75.75 0 0 1 4.5 20.25V6a2.25 2.25 0 0 1 2.25-2.25Z"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button id="tombol-perkecil" type="button"
                            class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">&minus;</button>
                    <button id="tombol-perbesar" type="button"
                            class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">+</button>
                    <button id="tombol-panel-penanda" type="button" aria-expanded="false" aria-controls="panel-penanda"
                            class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">
                        Penanda
                    </button>

                    @if ($aturan['boleh'])
                        <button id="tombol-unduh" type="button"
                                class="rounded-sm bg-jingga-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-jingga-700 disabled:opacity-60">
                            Unduh PDF
                        </button>
                    @endif
                </div>
            </div>

            <div id="panel-penanda" class="mt-3 hidden border border-kabut-200 bg-white px-4 py-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-medium text-kabut-700">Halaman bertanda</p>
                    <p class="text-xs text-kabut-500"><span id="jumlah-penanda">0</span> penanda</p>
                </div>
                <p id="pesan-penanda-kosong" class="text-sm text-kabut-500">Belum ada halaman yang ditandai</p>
                <ul id="daftar-penanda" class="flex flex-wrap gap-2"></ul>
            </div>

            {{-- Keterangan aturan unduh --}}
            <p class="mt-3 border-l-2 {{ $aturan['boleh'] ? 'border-jingga-600' : 'border-kabut-400' }} bg-white px-4 py-3 text-sm text-kabut-600">
                {{ $aturan['alasan'] }}
                @if ($buku->watermark_enabled && $aturan['boleh'])
                    Berkas unduhan akan mencantumkan nama dan email Anda.
                @endif
            </p>

            {{-- Area penampil --}}
            <div id="pembaca-pdf"
                 class="mt-4 flex justify-center overflow-auto border border-kabut-200 bg-kabut-200 p-4"
                 data-url-berkas="{{ route('katalog.berkas', $buku) }}"
                 data-url-catat="{{ route('katalog.catat', $buku) }}"
                 data-url-data-baca="{{ route('katalog.data-baca', $buku) }}"
                 data-url-progres="{{ route('katalog.progres', $buku) }}"
                 data-url-penanda="{{ route('katalog.penanda', $buku) }}"
                 data-csrf="{{ csrf_token() }}"
                 data-nama-berkas="{{ $buku->slug }}.pdf"
                 data-hal-awal="{{ $aturan['awal'] }}"
                 data-hal-akhir="{{ $aturan['akhir'] }}"
                 data-watermark="{{ $buku->watermark_enabled ? auth()->user()->name.' — '.auth()->user()->email : '' }}"
                 @if($buku->watermark_enabled) data-watermark-kaki="Diunduh oleh {{ auth()->user()->name }} ({{ auth()->user()->email }}) pada {{ now()->format('d/m/Y') }}" @endif>

                <div class="w-full max-w-3xl">
                    <p id="status-pembaca" class="py-16 text-center text-sm text-kabut-600">Memuat berkas…</p>
                    <div id="daftar-halaman" class="mx-auto w-full"></div>
                </div>
            </div>

            <p class="mt-3 text-xs text-kabut-500">
                Gunakan tombol panah kiri dan kanan pada papan ketik untuk berpindah halaman.
            </p>
        </div>
    </div>
</x-app-layout>