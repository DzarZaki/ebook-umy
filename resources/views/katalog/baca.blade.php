<x-app-layout>
    <x-slot name="title">Baca: {{ $buku->title }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('katalog.show', $buku) }}"
                   class="sapu-bawah cursor-pointer text-label font-semibold uppercase text-netral-500 dark:text-netral-400 transition-colors duration-150 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                    &larr; Kembali ke detail buku
                </a>
                <h2 class="mt-1.5 truncate text-lg font-semibold leading-tight text-netral-900 dark:text-netral-50">{{ $buku->title }}</h2>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                {{-- Menyimpan buku dari dalam pembaca --}}
                <x-tombol-simpan :buku="$buku"
                                 :tersimpan="auth()->user()->telahMenyimpan($buku)"
                                 gaya="ikon" />

                <span class="text-label font-semibold uppercase text-netral-500">{{ $buku->labelAkses() }}</span>
            </div>
        </div>
    </x-slot>

    <div class="pb-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            {{-- BILAH KENDALI --}}
            <div class="sticky top-16 z-30 -mx-4 border-b border-netral-200 dark:border-arang-600 bg-white/90 dark:bg-arang-800/90 px-4 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 transition-colors">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 py-3">

                    {{-- Rumpun 1: perpindahan halaman --}}
                    <div class="flex items-center gap-2">
                        <button id="tombol-sebelum" type="button"
                                aria-label="Halaman sebelumnya"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 motion-reduce:transition-none">
                            &larr;
                        </button>

                        <div class="flex items-baseline gap-2">
                            <input id="isian-halaman" type="number" min="1" value="1"
                                   aria-label="Nomor halaman"
                                   class="w-16 rounded border-netral-300 dark:border-arang-600 bg-white dark:bg-arang-700 py-1 text-center font-display text-base font-semibold text-netral-900 dark:text-netral-100 focus:border-jingga-500 focus:ring-jingga-500">
                            <span class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">
                                dari <span id="total-halaman">…</span>
                            </span>
                        </div>

                        <button id="tombol-sesudah" type="button"
                                aria-label="Halaman berikutnya"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 motion-reduce:transition-none">
                            &rarr;
                        </button>

                        <span class="mx-1 hidden h-6 w-px bg-netral-200 dark:bg-arang-600 sm:block" aria-hidden="true"></span>

                        <button id="tombol-penanda" type="button" aria-pressed="false"
                                aria-label="Tandai halaman ini" title="Tandai halaman ini"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-jingga-300 hover:bg-jingga-50 dark:hover:bg-arang-700 hover:text-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 aria-pressed:border-jingga-600 aria-pressed:bg-jingga-50 dark:aria-pressed:bg-arang-700 aria-pressed:text-jingga-600 motion-reduce:transition-none">
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

                    {{-- Rumpun 2 & 3: tampilan dan unduhan --}}
                    <div class="flex items-center gap-2">
                        <button id="tombol-perkecil" type="button"
                                aria-label="Perkecil tampilan halaman" title="Perkecil tampilan halaman"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-base text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">&minus;</button>

                        <button id="tombol-perbesar" type="button"
                                aria-label="Perbesar tampilan halaman" title="Perbesar tampilan halaman"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-base text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">+</button>

                        <span class="mx-1 hidden h-6 w-px bg-netral-200 dark:bg-arang-600 sm:block" aria-hidden="true"></span>

                        <button id="tombol-panel-penanda" type="button" aria-expanded="false" aria-controls="panel-penanda"
                                class="cursor-pointer rounded border border-netral-300 dark:border-arang-500 px-3 py-1.5 text-sm font-medium text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            Penanda
                        </button>

                        @if ($aturan['boleh'])
                            <a id="tautan-unduh" href="{{ route('katalog.unduh', $buku) }}"
                               class="inline-flex cursor-pointer items-center rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-1.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none shadow-sm">
                                Unduh PDF
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center rounded border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-4 py-1.5 text-sm font-medium text-netral-400"
                                  title="{{ $aturan['alasan'] }}">
                                Unduh tidak tersedia
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Panel penanda --}}
                <div id="panel-penanda" class="hidden max-h-[40vh] overflow-y-auto border-l-2 border-jingga-600 pb-4 pl-4 pt-1">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">Halaman bertanda</p>
                        <p class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">
                            <span id="jumlah-penanda" class="font-display text-base text-netral-900 dark:text-netral-100">0</span> penanda
                        </p>
                    </div>
                    <p id="pesan-penanda-kosong" class="text-sm text-netral-500 dark:text-netral-400">Belum ada halaman yang ditandai</p>
                    <ul id="daftar-penanda" class="flex flex-wrap gap-2"></ul>
                </div>
            </div>

            {{-- Keterangan aturan unduh --}}
            <p class="mt-4 border-l-2 {{ $aturan['boleh'] ? 'border-jingga-600 dark:border-jingga-400' : 'border-netral-300 dark:border-arang-600' }} bg-white/70 dark:bg-arang-800/60 px-4 py-3 text-sm leading-relaxed text-netral-700 dark:text-netral-300">
                {{ $aturan['alasan'] }}
                @if ($buku->watermark_enabled && $aturan['boleh'])
                    Nama dan email Anda akan tercantum pada setiap halaman berkas unduhan.
                @endif
            </p>

            {{-- MEJA BACA --}}
            <div id="pembaca-pdf"
                 class="tekstur-kertas mt-4 flex justify-center overflow-auto border border-netral-200 dark:border-arang-600 bg-netral-200/80 dark:bg-arang-900/90 p-4 sm:p-8 rounded"
                 data-url-berkas="{{ route('katalog.berkas', $buku) }}"
                 data-url-data-baca="{{ route('katalog.data-baca', $buku) }}"
                 data-url-progres="{{ route('katalog.progres', $buku) }}"
                 data-url-penanda="{{ route('katalog.penanda', $buku) }}"
                 data-csrf="{{ csrf_token() }}"
                 data-watermark="{{ $buku->watermark_enabled ? auth()->user()->name.' — '.auth()->user()->email : '' }}">

                <div class="w-full max-w-3xl">
                    <p id="status-pembaca" class="py-20 text-center text-sm text-netral-600 dark:text-netral-400">Memuat berkas…</p>
                    <div id="daftar-halaman" class="mx-auto w-full"></div>
                </div>
            </div>

            <p class="mt-4 text-label font-semibold uppercase text-netral-500 dark:text-netral-400">
                Tombol panah kiri dan kanan pada papan ketik juga memindahkan halaman
            </p>
        </div>
    </div>
</x-app-layout>