<x-app-layout>
    <x-slot name="title">Baca: {{ $buku->title }}</x-slot>

    <x-slot name="header">
        <div id="header-baca" class="flex flex-wrap items-center justify-between gap-4 transition-all duration-300">
            <div class="min-w-0">
                <a href="{{ route('katalog.show', $buku) }}"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-netral-500 dark:text-netral-400 transition-colors duration-150 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                    <span aria-hidden="true">&larr;</span> Kembali ke detail buku
                </a>
                <h1 class="mt-1 truncate font-display text-lg font-semibold leading-tight text-netral-900 dark:text-netral-50 sm:text-xl">{{ $buku->title }}</h1>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                {{-- Menyimpan buku dari dalam pembaca --}}
                <x-tombol-simpan :buku="$buku"
                                 :tersimpan="auth()->user()->telahMenyimpan($buku)"
                                 gaya="ikon" />

                <span class="rounded border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700 px-2.5 py-1 text-xs font-medium text-netral-600 dark:text-netral-300">{{ $buku->labelAkses() }}</span>
            </div>
        </div>
    </x-slot>

    <div id="lingkup-meja-baca" class="pb-12 transition-all duration-300">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            {{-- BILAH KENDALI MEJA BACA --}}
            <div id="bilah-kendali" class="sticky top-16 z-30 -mx-4 rounded-b-xl border-b border-x border-netral-200 dark:border-arang-600 bg-white/95 dark:bg-arang-800/95 px-4 backdrop-blur-md shadow-sm sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 transition-all duration-300">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 py-3">

                    {{-- Rumpun 1: navigasi halaman, penanda, dan catatan --}}
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <button id="tombol-sebelum" type="button"
                                aria-label="Halaman sebelumnya" title="Halaman sebelumnya (Panah Kiri)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 disabled:cursor-not-allowed disabled:opacity-30 btn-press">
                            &larr;
                        </button>

                        <div class="flex items-baseline gap-1.5">
                            <input id="isian-halaman" type="number" min="1" value="1"
                                   aria-label="Nomor halaman"
                                   class="h-9 w-14 sm:w-16 rounded border-netral-300 dark:border-arang-600 bg-white dark:bg-arang-700 py-1 text-center font-display text-sm sm:text-base font-semibold text-netral-900 dark:text-netral-100 focus:border-jingga-500 focus:ring-1 focus:ring-jingga-500 shadow-sm">
                            <span class="text-xs font-semibold uppercase text-netral-500 dark:text-netral-400 whitespace-nowrap">
                                dari <span id="total-halaman">…</span>
                            </span>
                        </div>

                        <button id="tombol-sesudah" type="button"
                                aria-label="Halaman berikutnya" title="Halaman berikutnya (Panah Kanan)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 disabled:cursor-not-allowed disabled:opacity-30 btn-press">
                            &rarr;
                        </button>

                        <span class="mx-1 hidden h-6 w-px bg-netral-200 dark:bg-arang-600 sm:block" aria-hidden="true"></span>

                        {{-- Tombol Penanda Halaman (Bookmark) --}}
                        <button id="tombol-penanda" type="button" aria-pressed="false"
                                aria-label="Tandai halaman ini" title="Tandai halaman ini (B)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-jingga-500 hover:bg-jingga-50 dark:hover:bg-arang-700 hover:text-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 aria-pressed:border-jingga-600 aria-pressed:bg-jingga-50 dark:aria-pressed:bg-arang-700 aria-pressed:text-jingga-600 btn-press">
                            <svg id="ikon-penanda-outline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 3.75H6.75A2.25 2.25 0 0 0 4.5 6v14.25l7.5-4.5 7.5 4.5V6a2.25 2.25 0 0 0-2.25-2.25Z"/>
                            </svg>
                            <svg id="ikon-penanda-isi" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                 fill="currentColor" class="hidden h-4 w-4">
                                <path d="M6.75 3.75h10.5A2.25 2.25 0 0 1 19.5 6v14.25a.75.75 0 0 1-1.136.643L12 17.079l-6.364 3.814A.75.75 0 0 1 4.5 20.25V6a2.25 2.25 0 0 1 2.25-2.25Z"/>
                            </svg>
                        </button>

                        {{-- Tombol Catatan Halaman (Notes) --}}
                        <button id="tombol-catatan" type="button"
                                aria-label="Tulis catatan di halaman ini" title="Catatan belajar halaman ini (N)"
                                class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-jingga-500 hover:bg-jingga-50 dark:hover:bg-arang-700 hover:text-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                            {{-- Dot indikator jika halaman ada catatan --}}
                            <span id="dot-catatan-aktif" class="hidden absolute top-1 right-1 h-2 w-2 rounded-full bg-jingga-600 dark:bg-jingga-400 ring-2 ring-white dark:ring-arang-800"></span>
                        </button>
                    </div>

                    {{-- Rumpun 2: tampilan zoom, skala lebar, panel samping, fullscreen, unduhan --}}
                    <div class="flex items-center gap-1.5 sm:gap-2">
                        <button id="tombol-perkecil" type="button"
                                aria-label="Perkecil tampilan" title="Perkecil tampilan (-)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-base text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press">&minus;</button>

                        <span id="label-zoom" class="hidden font-display text-xs font-semibold text-netral-600 dark:text-netral-300 sm:inline-block w-10 text-center">130%</span>

                        <button id="tombol-perbesar" type="button"
                                aria-label="Perbesar tampilan" title="Perbesar tampilan (+)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-base text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press">+</button>

                        {{-- Tombol Sesuaikan Lebar (Fit to Width) --}}
                        <button id="tombol-lebar-penuh" type="button"
                                aria-label="Sesuaikan lebar halaman" title="Sesuaikan lebar halaman (W)"
                                class="hidden xs:flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                            </svg>
                        </button>

                        <span class="mx-1 hidden h-6 w-px bg-netral-200 dark:bg-arang-600 sm:block" aria-hidden="true"></span>

                        {{-- Tombol Buka Smart Drawer (Penanda & Catatan) --}}
                        <button id="tombol-buka-drawer" type="button"
                                aria-label="Buka daftar penanda dan catatan" title="Daftar Penanda & Catatan"
                                class="inline-flex h-9 cursor-pointer items-center justify-center rounded border border-netral-300 dark:border-arang-500 px-3 text-xs font-semibold text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-netral-500 dark:hover:border-arang-300 hover:text-netral-900 dark:hover:text-netral-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press">
                            <span class="hidden sm:inline">Daftar&nbsp;</span>Penanda &amp; Catatan
                        </button>

                        {{-- Tombol Mode Fokus / Layar Penuh (Zen Mode) --}}
                        <button id="tombol-fokus" type="button"
                                aria-label="Mode Fokus Layar Penuh" title="Mode Fokus / Layar Penuh (F)"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-netral-300 dark:border-arang-500 text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:border-jingga-500 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press">
                            <svg id="ikon-fokus-masuk" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v5.25m0-5.25h5.25m-5.25 0 6 6m10.5-6v5.25m0-5.25h-5.25m5.25 0-6 6m-10.5 10.5v-5.25m0 5.25h5.25m-5.25 0 6-6m10.5 6v-5.25m0 5.25h-5.25m5.25 0-6-6" />
                            </svg>
                            <svg id="ikon-fokus-keluar" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="hidden h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V3.75M9 9H3.75M9 9 3.75 3.75M9 15v5.25M9 15H3.75M9 15l-5.25 5.25M15 9V3.75M15 9h5.25M15 9l5.25-5.25M15 15v5.25M15 15h5.25M15 15l5.25 5.25" />
                            </svg>
                        </button>

                        @if ($aturan['boleh'])
                            {{-- Di layar kecil cukup ikonnya; label teks muncul mulai md. --}}
                            <a id="tautan-unduh" href="{{ route('katalog.unduh', $buku) }}"
                               aria-label="Unduh berkas buku" title="Unduh berkas"
                               class="inline-flex h-9 w-9 md:w-auto cursor-pointer items-center justify-center gap-1.5 rounded bg-jingga-600 dark:bg-jingga-500 px-2.5 md:px-3.5 text-xs font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 btn-press shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 md:hidden" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v10.5m0 0 4.5-4.5M12 15l-4.5-4.5M4.5 19.5h15" />
                                </svg>
                                <span class="hidden md:inline">Unduh</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Keterangan aturan unduh & hak cipta --}}
            <div id="info-aturan-baca" class="mt-5 rounded-lg border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/70 p-3.5 text-xs leading-relaxed text-netral-600 dark:text-netral-300 shadow-sm transition-all duration-300">
                <span class="font-semibold text-netral-800 dark:text-netral-200">Akses berkas:</span> {{ $aturan['alasan'] }}
                @if ($buku->watermark_enabled && $aturan['boleh'])
                    <span class="block mt-1 text-netral-500 dark:text-netral-400">Nama dan email Anda tercantum otomatis pada berkas unduhan sebagai tanda kepemilikan.</span>
                @endif
            </div>

            {{-- MEJA BACA PDF --}}
            <div id="pembaca-pdf"
                 class="tekstur-kertas mt-5 flex justify-center overflow-auto rounded-xl border border-netral-200 dark:border-arang-600 bg-netral-200/80 dark:bg-arang-900/90 p-4 sm:p-8 shadow-inner transition-all duration-300"
                 data-url-berkas="{{ route('katalog.berkas', $buku) }}"
                 data-url-data-baca="{{ route('katalog.data-baca', $buku) }}"
                 data-url-progres="{{ route('katalog.progres', $buku) }}"
                 data-url-penanda="{{ route('katalog.penanda', $buku) }}"
                 data-url-catatan-simpan="{{ route('katalog.catatan.simpan', $buku) }}"
                 data-url-catatan-hapus="{{ route('katalog.catatan.hapus', $buku) }}"
                 data-csrf="{{ csrf_token() }}"
                 data-watermark="{{ $buku->watermark_enabled ? auth()->user()->name.' — '.auth()->user()->email : '' }}">

                <div class="w-full max-w-3xl">
                    <p id="status-pembaca" class="py-20 text-center text-sm font-medium text-netral-600 dark:text-netral-400">Memuat lembar bacaan…</p>
                    <div id="daftar-halaman" class="mx-auto w-full"></div>
                </div>
            </div>

            <p id="petunjuk-keyboard" class="mt-4 text-center text-xs text-netral-500 dark:text-netral-400 sm:text-start transition-all">
                Pintasan keyboard: <kbd class="rounded border border-netral-300 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] font-mono">&larr;</kbd>/<kbd class="rounded border border-netral-300 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] font-mono">&rarr;</kbd> pindah halaman &middot; <kbd class="rounded border border-netral-300 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] font-mono">F</kbd> mode fokus &middot; <kbd class="rounded border border-netral-300 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] font-mono">B</kbd> penanda &middot; <kbd class="rounded border border-netral-300 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] font-mono">N</kbd> catatan
            </p>
        </div>
    </div>

    {{-- =========================================================================
         POPOVER / MODAL CATATAN HALAMAN
         ========================================================================= --}}
    <div id="modal-catatan" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-arang-900/60 backdrop-blur-sm transition-opacity">
        <div class="w-full max-w-lg rounded-2xl border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 shadow-2xl transition-all">
            <div class="flex items-center justify-between border-b border-netral-200 dark:border-arang-600 pb-3">
                <div class="flex items-center gap-2">
                    <span class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Catatan Halaman <span id="judul-halaman-catatan" class="text-jingga-600 dark:text-jingga-400">1</span></span>
                </div>
                <button id="tombol-tutup-modal-catatan" type="button" class="rounded p-1 text-netral-400 hover:text-netral-700 dark:hover:text-netral-200">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </div>

            <div class="mt-4">
                <textarea id="isian-teks-catatan" rows="5"
                          class="w-full rounded-lg border border-netral-300 dark:border-arang-600 bg-netral-50 dark:bg-arang-700/60 p-3 text-sm text-netral-900 dark:text-netral-100 placeholder-netral-400 focus:border-jingga-500 focus:ring-1 focus:ring-jingga-500"
                          placeholder="Tuliskan catatan penting, rumus, atau ringkasan materi untuk halaman ini..."></textarea>
                <div class="mt-1 flex items-center justify-between text-xs text-netral-500 dark:text-netral-400">
                    <span id="info-waktu-catatan"></span>
                    <span>Tersimpan otomatis</span>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between">
                <button id="tombol-hapus-catatan-modal" type="button"
                        class="hidden text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">
                    Hapus Catatan
                </button>
                <div class="flex items-center gap-2 ms-auto">
                    <button id="tombol-batal-catatan" type="button"
                            class="rounded border border-netral-300 dark:border-arang-600 px-4 py-2 text-xs font-semibold text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700">
                        Tutup
                    </button>
                    <button id="tombol-simpan-catatan-modal" type="button"
                            class="rounded bg-jingga-600 dark:bg-jingga-500 px-5 py-2 text-xs font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 btn-press shadow-sm">
                        Simpan Catatan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         SMART SLIDE-OVER DRAWER (PENANDA & CATATAN)
         ========================================================================= --}}
    <div id="drawer-penanda" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div id="drawer-backdrop" class="fixed inset-0 bg-arang-900/60 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div id="drawer-panel" class="w-screen max-w-md bg-white dark:bg-arang-800 shadow-2xl border-l border-netral-200 dark:border-arang-600 transform translate-x-full transition-transform duration-300 flex flex-col">

                {{-- Header Drawer --}}
                <div class="p-6 border-b border-netral-200 dark:border-arang-600 flex items-center justify-between">
                    <div>
                        <h3 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Daftar Bacaan Anda</h3>
                        <p class="text-xs text-netral-500 dark:text-netral-400 mt-0.5">{{ $buku->title }}</p>
                    </div>
                    <button id="tombol-tutup-drawer" type="button" class="rounded p-1.5 text-netral-400 hover:text-netral-700 dark:hover:text-netral-200">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </div>

                {{-- Tab Navigasi Drawer: Penanda vs Catatan --}}
                <div class="flex border-b border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700/50">
                    <button id="tab-drawer-penanda" type="button"
                            class="flex-1 py-3 text-xs font-semibold uppercase tracking-wider text-center border-b-2 border-jingga-600 text-jingga-600 dark:text-jingga-400 bg-white dark:bg-arang-800 transition">
                        Penanda (<span id="jumlah-penanda-tab">0</span>)
                    </button>
                    <button id="tab-drawer-catatan" type="button"
                            class="flex-1 py-3 text-xs font-semibold uppercase tracking-wider text-center border-b-2 border-transparent text-netral-500 dark:text-netral-400 hover:text-netral-800 dark:hover:text-netral-200 transition">
                        Catatan (<span id="jumlah-catatan-tab">0</span>)
                    </button>
                </div>

                {{-- Konten Tab 1: Penanda --}}
                <div id="konten-tab-penanda" class="flex-1 overflow-y-auto p-6 space-y-3">
                    <p id="pesan-penanda-kosong" class="text-sm text-netral-500 dark:text-netral-400 text-center py-10">Belum ada halaman yang ditandai untuk buku ini.</p>
                    <ul id="daftar-penanda" class="flex flex-wrap gap-2.5"></ul>
                </div>

                {{-- Konten Tab 2: Catatan --}}
                <div id="konten-tab-catatan" class="hidden flex-1 overflow-y-auto p-6 space-y-4">
                    <p id="pesan-catatan-kosong" class="text-sm text-netral-500 dark:text-netral-400 text-center py-10">Belum ada catatan belajar di buku ini.</p>
                    <div id="daftar-catatan" class="space-y-3"></div>
                </div>

                {{-- Footer Drawer --}}
                <div class="p-4 border-t border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700/30 text-center">
                    <button id="tombol-tambah-catatan-drawer" type="button"
                            class="w-full rounded bg-jingga-600 dark:bg-jingga-500 py-2.5 text-xs font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 btn-press">
                        + Tulis Catatan di Halaman Ini
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>