<x-app-layout>
    <x-slot name="title">Baca: {{ $buku->title }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('katalog.show', $buku) }}"
                   class="sapu-bawah cursor-pointer text-label font-semibold uppercase text-kabut-400 transition-colors duration-150 hover:text-kabut-100 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                    &larr; Kembali ke detail buku
                </a>
                <h2 class="mt-1.5 truncate text-lg font-semibold leading-tight text-kabut-50">{{ $buku->title }}</h2>
            </div>

            <div class="flex shrink-0 items-center gap-3">
                {{-- Menyimpan buku dari dalam pembaca: keinginan menyimpan
                     paling sering muncul justru saat sedang membaca. --}}
                <x-tombol-simpan :buku="$buku"
                                 :tersimpan="auth()->user()->telahMenyimpan($buku)"
                                 gaya="ikon" />

                <span class="text-label font-semibold uppercase text-kabut-500">{{ $buku->labelAkses() }}</span>
            </div>
        </div>
    </x-slot>

    <div class="pb-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            {{--
                BILAH KENDALI
                Menempel tepat di bawah bilah navigasi (top-16) saat digulir.
                z-30 menempatkannya di atas kanvas PDF namun tetap di bawah
                navigasi yang z-40.

                Dibagi menjadi tiga rumpun yang dipisahkan garis rambut, bukan
                sederet tombol seragam: perpindahan halaman di kiri, pengaturan
                tampilan di tengah, unduhan di kanan. Mata mencari tombol
                berdasarkan letaknya, dan letak hanya bermakna bila ada
                pengelompokan.

                Panel penanda berada DI DALAM bilah ini, bukan di bawahnya,
                sehingga keduanya bergerak sebagai satu benda saat digulir.
            --}}
            <div class="sticky top-16 z-30 -mx-4 border-b garis-tinta bg-sepia-50/85 px-4 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 py-3">

                    {{-- Rumpun 1: perpindahan halaman --}}
                    <div class="flex items-center gap-2">
                        <button id="tombol-sebelum" type="button"
                                aria-label="Halaman sebelumnya"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-kabut-300 text-kabut-600 transition-colors duration-150 hover:border-kabut-500 hover:text-sepia-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 motion-reduce:transition-none">
                            &larr;
                        </button>

                        <div class="flex items-baseline gap-2">
                            <input id="isian-halaman" type="number" min="1" value="1"
                                   aria-label="Nomor halaman"
                                   class="w-16 rounded border-kabut-300 py-1 text-center font-display text-base font-semibold text-sepia-800 focus:border-jingga-500 focus:ring-jingga-500">
                            <span class="text-label font-semibold uppercase text-kabut-500">
                                dari <span id="total-halaman">…</span>
                            </span>
                        </div>

                        <button id="tombol-sesudah" type="button"
                                aria-label="Halaman berikutnya"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-kabut-300 text-kabut-600 transition-colors duration-150 hover:border-kabut-500 hover:text-sepia-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-30 motion-reduce:transition-none">
                            &rarr;
                        </button>

                        <span class="mx-1 hidden h-6 w-px bg-kabut-200 sm:block" aria-hidden="true"></span>

                        <button id="tombol-penanda" type="button" aria-pressed="false"
                                aria-label="Tandai halaman ini" title="Tandai halaman ini"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-kabut-300 text-kabut-600 transition-colors duration-150 hover:border-jingga-300 hover:bg-jingga-50 hover:text-jingga-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 aria-pressed:border-jingga-300 aria-pressed:bg-jingga-50 aria-pressed:text-jingga-700 motion-reduce:transition-none">
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
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-kabut-300 text-base text-kabut-600 transition-colors duration-150 hover:border-kabut-500 hover:text-sepia-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">&minus;</button>

                        <button id="tombol-perbesar" type="button"
                                aria-label="Perbesar tampilan halaman" title="Perbesar tampilan halaman"
                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-kabut-300 text-base text-kabut-600 transition-colors duration-150 hover:border-kabut-500 hover:text-sepia-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">+</button>

                        <span class="mx-1 hidden h-6 w-px bg-kabut-200 sm:block" aria-hidden="true"></span>

                        <button id="tombol-panel-penanda" type="button" aria-expanded="false" aria-controls="panel-penanda"
                                class="cursor-pointer rounded border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 transition-colors duration-150 hover:border-kabut-500 hover:text-sepia-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            Penanda
                        </button>

                        {{--
                            Unduhan diminta ke server, bukan dirakit di browser.
                            Sebuah tautan biasa sudah cukup: server yang memotong
                            halaman, menempelkan stempel identitas, dan mencatat
                            unduhannya. Nama `id` sengaja berbeda dari `tombol-unduh`
                            agar penampil PDF tidak memasang penangan klik lama.
                        --}}
                        @if ($aturan['boleh'])
                            <a id="tautan-unduh" href="{{ route('katalog.unduh', $buku) }}"
                               class="inline-flex cursor-pointer items-center rounded bg-jingga-600 px-4 py-1.5 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                                Unduh PDF
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center rounded border border-kabut-200 bg-kabut-50 px-4 py-1.5 text-sm font-medium text-kabut-400"
                                  title="{{ $aturan['alasan'] }}">
                                Unduh tidak tersedia
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Panel penanda. Kelas `hidden` awal WAJIB tetap ada:
                     pembaca-pdf.js baris 485 dan 529 membuka-tutup panel ini
                     dengan classList.toggle('hidden', …).

                     Tingginya dibatasi 40% layar lalu diberi gulir sendiri:
                     pembaca dengan tiga puluh penanda tidak boleh kehilangan
                     seluruh halaman bacaannya di balik daftar penanda. --}}
                <div id="panel-penanda" class="hidden max-h-[40vh] overflow-y-auto border-l-2 border-jingga-600 pb-4 pl-4 pt-1">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-label font-semibold uppercase text-kabut-500">Halaman bertanda</p>
                        <p class="text-label font-semibold uppercase text-kabut-500">
                            <span id="jumlah-penanda" class="font-display text-base text-sepia-800">0</span> penanda
                        </p>
                    </div>
                    <p id="pesan-penanda-kosong" class="text-sm text-kabut-500">Belum ada halaman yang ditandai</p>
                    <ul id="daftar-penanda" class="flex flex-wrap gap-2"></ul>
                </div>
            </div>

            {{-- Keterangan aturan unduh. Latar putih pekatnya diganti krem
                 tembus pandang agar menyatu dengan dasar kertas. --}}
            <p class="mt-4 border-l-2 {{ $aturan['boleh'] ? 'border-jingga-600' : 'border-kabut-300' }} bg-sepia-50/70 px-4 py-3 text-sm leading-relaxed text-kabut-600">
                {{ $aturan['alasan'] }}
                @if ($buku->watermark_enabled && $aturan['boleh'])
                    Nama dan email Anda akan tercantum pada setiap halaman berkas unduhan.
                @endif
            </p>

            {{--
                MEJA BACA
                Latar kabut-200 sengaja lebih gelap daripada halamannya: itu
                yang membuat lembar putih terbaca sebagai kertas yang terletak
                di atas meja, bukan sebagai bidang tanpa batas.

                Saya TIDAK menggelapkan meja ini menjadi sepia tua, walaupun
                secara desain akan jauh lebih dramatis. Alasannya: baris 298–303
                pembaca-pdf.js menyuntikkan pesan galat beserta tombol coba lagi
                ke dalam kotak ini, dan saya belum melihat warna teks yang
                dipakainya. Meja gelap berisiko membuat pesan galat itu menjadi
                teks kelabu di atas cokelat tua — tidak terbaca justru pada saat
                pengguna paling membutuhkannya.

                Susunan di dalamnya — #pembaca-pdf > div.w-full.max-w-3xl >
                (#status-pembaca, #daftar-halaman) — dipertahankan persis,
                karena lebar kanvas dihitung dari kotak itu.
            --}}
            <div id="pembaca-pdf"
                 class="tekstur-kertas mt-4 flex justify-center overflow-auto border border-kabut-200 bg-kabut-200 p-4 sm:p-8"
                 data-url-berkas="{{ route('katalog.berkas', $buku) }}"
                 data-url-data-baca="{{ route('katalog.data-baca', $buku) }}"
                 data-url-progres="{{ route('katalog.progres', $buku) }}"
                 data-url-penanda="{{ route('katalog.penanda', $buku) }}"
                 data-csrf="{{ csrf_token() }}"
                 data-watermark="{{ $buku->watermark_enabled ? auth()->user()->name.' — '.auth()->user()->email : '' }}">

                <div class="w-full max-w-3xl">
                    <p id="status-pembaca" class="py-20 text-center text-sm text-kabut-600">Memuat berkas…</p>
                    <div id="daftar-halaman" class="mx-auto w-full"></div>
                </div>
            </div>

            <p class="mt-4 text-label font-semibold uppercase text-kabut-400">
                Tombol panah kiri dan kanan pada papan ketik juga memindahkan halaman
            </p>
        </div>
    </div>
</x-app-layout>