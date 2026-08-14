<x-app-layout>
    <x-slot name="title">Katalog</x-slot>

    @php
        $adaPenyaring = $kueri || $kategoriId || $lingkup !== 'semua' || $urut !== 'terbaru';
    @endphp

    {{-- =====================================================================
         1. KEPALA
         Jumlah judul dicetak sebagai angka besar di sebelah kata "Katalog",
         bukan sebagai keterangan kecil di sudut. Di halaman pencarian,
         berapa banyak yang ditemukan adalah kabar utamanya.
         ===================================================================== --}}
    <section class="border-b border-sepia-700 bg-gradient-to-br from-sepia-900 via-sepia-800 to-sepia-900">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-6" data-muncul>
                <div>
                    <p class="text-label font-semibold uppercase text-jingga-400">
                        @if (auth()->user()->isSuperAdmin())
                            Seluruh program studi
                        @else
                            {{ auth()->user()->prodi?->name ?? 'Program studi Anda' }} &amp; bacaan umum
                        @endif
                    </p>

                    <h1 class="judul-raksasa mt-3 text-besar text-kabut-50">
                        Katalog
                    </h1>
                </div>

                <p class="flex items-baseline gap-3">
                    <span class="font-display text-5xl font-semibold leading-none text-kabut-50 sm:text-6xl">
                        {{ str_pad($daftarBuku->total(), 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <span class="text-label font-semibold uppercase text-kabut-400">
                        judul<br>ditemukan
                    </span>
                </p>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         2. PENYARING
         Tetap form GET biasa: bekerja tanpa JavaScript, bisa disalin sebagai
         tautan, dan bisa ditandai sebagai favorit peramban.
         ===================================================================== --}}
    <section class="border-b border-sepia-700 bg-sepia-800/50 backdrop-blur-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('katalog.index') }}"
                  class="grid gap-4 lg:grid-cols-12 lg:items-end">

                <div class="lg:col-span-5">
                    <x-input-label for="q" value="Cari judul, penulis, atau deskripsi" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 w-full" :value="$kueri"
                                  placeholder="mis. manajemen operasional" />
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="kategori" value="Kategori" />
                    <select id="kategori" name="kategori"
                            class="mt-1 block w-full cursor-pointer rounded border-sepia-700 bg-sepia-800 text-kabut-100 text-sm focus:border-jingga-500 focus:ring-jingga-500">
                        <option value="">Semua kategori</option>
                        @foreach ($daftarKategori as $kategori)
                            <option value="{{ $kategori->id }}" @selected($kategoriId == $kategori->id)>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="lingkup" value="Lingkup" />
                    <select id="lingkup" name="lingkup"
                            class="mt-1 block w-full cursor-pointer rounded border-sepia-700 bg-sepia-800 text-kabut-100 text-sm focus:border-jingga-500 focus:ring-jingga-500">
                        <option value="semua" @selected($lingkup === 'semua')>Semua</option>
                        <option value="prodi" @selected($lingkup === 'prodi')>Program studi</option>
                        <option value="umum" @selected($lingkup === 'umum')>Umum</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="urut" value="Urutkan" />
                    <select id="urut" name="urut"
                            class="mt-1 block w-full cursor-pointer rounded border-sepia-700 bg-sepia-800 text-kabut-100 text-sm focus:border-jingga-500 focus:ring-jingga-500">
                        <option value="terbaru" @selected($urut === 'terbaru')>Terbaru</option>
                        <option value="judul" @selected($urut === 'judul')>Judul A&ndash;Z</option>
                    </select>
                </div>

                <div class="flex items-center gap-4 lg:col-span-1">
                    <button type="submit"
                            class="w-full cursor-pointer rounded bg-jingga-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 focus-visible:ring-offset-sepia-900 motion-reduce:transition-none">
                        Cari
                    </button>
                </div>

                @if ($adaPenyaring)
                    <div class="flex flex-wrap items-center gap-3 border-t border-sepia-700 pt-4 lg:col-span-12">
                        <span class="text-label font-semibold uppercase text-kabut-400">Penyaring aktif</span>

                        @if ($kueri)
                            <span class="rounded border border-jingga-600/50 bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-300">
                                &ldquo;{{ $kueri }}&rdquo;
                            </span>
                        @endif
                        @if ($lingkup !== 'semua')
                            <span class="rounded border border-sepia-600 bg-sepia-800 px-2 py-1 text-xs font-medium text-kabut-300">
                                {{ $lingkup === 'prodi' ? 'Program studi' : 'Umum' }}
                            </span>
                        @endif
                        @if ($urut !== 'terbaru')
                            <span class="rounded border border-sepia-600 bg-sepia-800 px-2 py-1 text-xs font-medium text-kabut-300">
                                Judul A&ndash;Z
                            </span>
                        @endif

                        <a href="{{ route('katalog.index') }}"
                           class="sapu-bawah cursor-pointer text-xs font-semibold text-kabut-400 transition-colors duration-150 hover:text-kabut-200 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            Hapus semua penyaring
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </section>

    {{-- =====================================================================
         3. HASIL
         ===================================================================== --}}
    <section class="bg-sepia-900 py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if ($daftarBuku->isEmpty())
                <div class="mx-auto max-w-md py-16 text-center" data-muncul>
                    <p class="text-besar text-kabut-50">Tidak ada yang cocok</p>
                    <p class="mt-4 text-sm leading-relaxed text-kabut-400">
                        @if ($kueri)
                            Tidak ditemukan hasil untuk &ldquo;{{ $kueri }}&rdquo;.
                            Coba kata kunci lain, atau longgarkan penyaringnya.
                        @else
                            Dosen pengampu belum menambahkan bacaan pada bagian ini.
                        @endif
                    </p>

                    @if ($adaPenyaring)
                        <a href="{{ route('katalog.index') }}"
                           class="mt-8 inline-flex cursor-pointer items-center rounded border border-sepia-600 bg-sepia-800 px-4 py-2 text-sm font-semibold text-kabut-300 transition-colors duration-150 hover:border-sepia-500 hover:bg-sepia-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 focus-visible:ring-offset-sepia-900 motion-reduce:transition-none">
                            Tampilkan semua judul
                        </a>
                    @endif
                </div>
            @else
                <ol class="border-t border-sepia-700" data-tahap="55">
                    @foreach ($daftarBuku as $i => $buku)
                        <li class="group border-b border-sepia-700 transition-colors duration-200 hover:bg-sepia-800/30 motion-reduce:transition-none"
                            data-muncul>
                            <div class="flex items-start gap-4 py-6 sm:gap-8">

                                <span class="w-7 shrink-0 pt-1 font-display text-lg text-kabut-500 transition-colors duration-200 group-hover:text-jingga-400 sm:w-12 sm:text-2xl">
                                    {{ str_pad($daftarBuku->firstItem() + $i, 2, '0', STR_PAD_LEFT) }}
                                </span>

                                <a href="{{ route('katalog.show', $buku) }}"
                                   tabindex="-1"
                                   aria-hidden="true"
                                   class="hidden w-16 shrink-0 xs:block sm:w-20">
                                    <x-buku-3d :buku="$buku" :punggung="false" />
                                </a>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($buku->isUmum())
                                            <span class="rounded border border-sepia-600 bg-sepia-800 px-2 py-0.5 text-[11px] font-medium text-sepia-300">Umum</span>
                                        @else
                                            <span class="rounded border border-jingga-600/50 bg-jingga-900/30 px-2 py-0.5 text-[11px] font-medium text-jingga-300">{{ $buku->prodi?->name ?? 'Umum' }}</span>
                                        @endif

                                        @if ($buku->category)
                                            <span class="rounded border border-sepia-600 bg-sepia-800 px-2 py-0.5 text-[11px] font-medium text-kabut-400">{{ $buku->category->name }}</span>
                                        @endif

                                        @if ($buku->created_at && $buku->created_at->gt(now()->subDays(14)))
                                            <span class="text-[11px] font-semibold uppercase tracking-wider text-jingga-400">Baru</span>
                                        @endif
                                    </div>

                                    <h3 class="mt-2 text-lg font-semibold leading-snug text-kabut-50 sm:text-xl">
                                        <a href="{{ route('katalog.show', $buku) }}"
                                           class="sapu-bawah cursor-pointer focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 focus-visible:ring-offset-sepia-900">
                                            {{ $buku->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-1.5 text-xs text-kabut-400 sm:text-sm">
                                        {{ $buku->author ?: 'Tanpa penulis' }}
                                        @if ($buku->page_count)
                                            &middot; {{ $buku->page_count }} halaman
                                        @endif
                                        &middot; {{ $buku->labelAkses() }}
                                    </p>

                                    @if ($buku->description)
                                        <p class="mt-3 max-w-2xl line-clamp-2 text-sm leading-relaxed text-kabut-500">
                                            {{ $buku->description }}
                                        </p>
                                    @endif
                                </div>

                                <div class="shrink-0 pt-1">
                                    <x-tombol-simpan :buku="$buku"
                                                     :tersimpan="auth()->user()->telahMenyimpan($buku)"
                                                     gaya="ikon" />
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-10">{{ $daftarBuku->links() }}</div>
            @endif
        </div>
    </section>
</x-app-layout>