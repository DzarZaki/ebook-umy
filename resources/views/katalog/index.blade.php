<x-app-layout>
    <x-slot name="title">Katalog</x-slot>

    @php
        $adaPenyaring = $kueri || $kategoriId || $lingkup !== 'semua' || $urut !== 'terbaru';
    @endphp

    {{-- =====================================================================
         1. KEPALA KATALOG
         ===================================================================== --}}
    <section class="border-b border-netral-200 dark:border-arang-600 bg-white/70 dark:bg-arang-800/80 backdrop-blur-sm transition-colors">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-6" data-muncul>
                <div>
                    <p class="text-label font-semibold uppercase text-jingga-600 dark:text-jingga-400">
                        @if (auth()->user()->isSuperAdmin())
                            Seluruh program studi
                        @else
                            {{ auth()->user()->prodi?->name ?? 'Program studi Anda' }} &amp; bacaan umum
                        @endif
                    </p>

                    <h1 class="judul-raksasa mt-3 text-besar text-netral-900 dark:text-netral-50">
                        Katalog
                    </h1>
                </div>

                <div class="flex items-baseline gap-3">
                    <span class="font-display text-4xl font-semibold leading-none text-netral-900 dark:text-netral-50 sm:text-5xl">
                        {{ str_pad($daftarBuku->total(), 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <span class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">
                        judul<br>ditemukan
                    </span>
                </div>
            </div>
        </div>
    </section>

    {{-- =====================================================================
         2. PENYARING
         ===================================================================== --}}
    <section class="border-b border-netral-200 dark:border-arang-600 bg-white/50 dark:bg-arang-800/50 backdrop-blur-sm transition-colors">
        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('katalog.index') }}"
                  class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">

                <div class="sm:col-span-2 lg:col-span-5">
                    <x-input-label for="q" value="Cari judul, penulis, atau topik" class="text-xs uppercase tracking-wider text-netral-500 dark:text-netral-400" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 h-10 w-full text-sm" :value="$kueri"
                                  placeholder="Ketik kata kunci pencarian..." />
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="kategori" value="Kategori" class="text-xs uppercase tracking-wider text-netral-500 dark:text-netral-400" />
                    <select id="kategori" name="kategori"
                            class="mt-1 block h-10 w-full cursor-pointer rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 text-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-1 focus:ring-jingga-500 shadow-sm">
                        <option value="">Semua kategori</option>
                        @foreach ($daftarKategori as $kategori)
                            <option value="{{ $kategori->id }}" @selected($kategoriId == $kategori->id)>
                                {{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="lingkup" value="Lingkup" class="text-xs uppercase tracking-wider text-netral-500 dark:text-netral-400" />
                    <select id="lingkup" name="lingkup"
                            class="mt-1 block h-10 w-full cursor-pointer rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 text-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-1 focus:ring-jingga-500 shadow-sm">
                        <option value="semua" @selected($lingkup === 'semua')>Semua</option>
                        <option value="prodi" @selected($lingkup === 'prodi')>Program studi</option>
                        <option value="umum" @selected($lingkup === 'umum')>Umum</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="urut" value="Urutkan" class="text-xs uppercase tracking-wider text-netral-500 dark:text-netral-400" />
                    <select id="urut" name="urut"
                            class="mt-1 block h-10 w-full cursor-pointer rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 text-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-1 focus:ring-jingga-500 shadow-sm">
                        <option value="terbaru" @selected($urut === 'terbaru')>Terbaru</option>
                        <option value="judul" @selected($urut === 'judul')>Judul A&ndash;Z</option>
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-1">
                    <button type="submit"
                            class="inline-flex h-10 w-full cursor-pointer items-center justify-center rounded bg-jingga-600 dark:bg-jingga-500 px-4 text-sm font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 btn-press shadow-sm">
                        Cari
                    </button>
                </div>

                @if ($adaPenyaring)
                    <div class="flex flex-wrap items-center gap-2.5 border-t border-netral-200 dark:border-arang-600 pt-3 sm:col-span-2 lg:col-span-12">
                        <span class="text-label font-semibold uppercase text-netral-500 dark:text-netral-400">Penyaring aktif:</span>

                        @if ($kueri)
                            <span class="inline-flex items-center gap-1 rounded border border-jingga-600/30 dark:border-jingga-500/40 bg-jingga-50 dark:bg-jingga-900/30 px-2.5 py-1 text-xs font-medium text-jingga-700 dark:text-jingga-300">
                                &ldquo;{{ $kueri }}&rdquo;
                            </span>
                        @endif
                        @if ($lingkup !== 'semua')
                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2.5 py-1 text-xs font-medium text-netral-700 dark:text-netral-300">
                                {{ $lingkup === 'prodi' ? 'Program studi' : 'Umum' }}
                            </span>
                        @endif
                        @if ($urut !== 'terbaru')
                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2.5 py-1 text-xs font-medium text-netral-700 dark:text-netral-300">
                                Judul A&ndash;Z
                            </span>
                        @endif

                        <a href="{{ route('katalog.index') }}"
                           class="sapu-bawah ms-1 cursor-pointer text-xs font-semibold text-jingga-600 dark:text-jingga-400 transition-colors duration-150 hover:text-jingga-700 dark:hover:text-jingga-300 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 motion-reduce:transition-none">
                            Hapus semua penyaring
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </section>

    {{-- =====================================================================
         3. DAFTAR HASIL KATALOG
         ===================================================================== --}}
    <section class="bg-transparent py-10 sm:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if ($daftarBuku->isEmpty())
                <div class="mx-auto max-w-md py-16 text-center" data-muncul>
                    <p class="font-display text-2xl font-semibold text-netral-900 dark:text-netral-50">Tidak ada yang cocok</p>
                    <p class="mt-3 text-sm leading-relaxed text-netral-600 dark:text-netral-400">
                        @if ($kueri)
                            Tidak ditemukan buku untuk kata kunci &ldquo;{{ $kueri }}&rdquo;.
                            Coba kata kunci lain atau longgarkan filter pencarian.
                        @else
                            Belum ada bacaan yang tersedia di katalog saat ini.
                        @endif
                    </p>

                    @if ($adaPenyaring)
                        <a href="{{ route('katalog.index') }}"
                           class="mt-6 inline-flex cursor-pointer items-center rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-4 py-2 text-sm font-semibold text-netral-700 dark:text-netral-300 transition-colors duration-150 hover:bg-netral-50 dark:hover:bg-arang-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2 shadow-sm">
                            Tampilkan semua judul
                        </a>
                    @endif
                </div>
            @else
                <ol class="border-t border-netral-200 dark:border-arang-600" data-tahap="55">
                    @foreach ($daftarBuku as $i => $buku)
                        <li class="group border-b border-netral-200 dark:border-arang-600 list-item-modern"
                            data-muncul>
                            <div class="flex items-start gap-4 px-2 py-5 sm:gap-8 sm:px-4">

                                <span class="w-7 shrink-0 pt-1 font-display text-lg text-netral-400 dark:text-netral-500 transition-colors duration-200 group-hover:text-jingga-600 dark:group-hover:text-jingga-400 sm:w-10 sm:text-2xl">
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
                                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2 py-0.5 text-[11px] font-medium text-netral-700 dark:text-netral-300 badge-category">Umum</span>
                                        @else
                                            <span class="rounded border border-jingga-200 dark:border-jingga-600/50 bg-jingga-50 dark:bg-jingga-900/30 px-2 py-0.5 text-[11px] font-medium text-jingga-700 dark:text-jingga-300 badge-category">{{ $buku->prodi?->name ?? 'Umum' }}</span>
                                        @endif

                                        @if ($buku->category)
                                            <span class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-2 py-0.5 text-[11px] font-medium text-netral-600 dark:text-netral-400 badge-category">{{ $buku->category->name }}</span>
                                        @endif

                                        @if ($buku->created_at && $buku->created_at->gt(now()->subDays(14)))
                                            <span class="rounded border border-jingga-200 dark:border-jingga-700/50 bg-jingga-50 dark:bg-jingga-900/30 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-jingga-700 dark:text-jingga-300">Baru</span>
                                        @endif
                                    </div>

                                    <h3 class="mt-2 font-display text-lg font-semibold leading-snug text-netral-900 dark:text-netral-50 sm:text-xl">
                                        <a href="{{ route('katalog.show', $buku) }}"
                                           class="sapu-bawah cursor-pointer focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-500 focus-visible:ring-offset-2">
                                            {{ $buku->title }}
                                        </a>
                                    </h3>

                                    <p class="mt-1 text-xs text-netral-500 dark:text-netral-400 sm:text-sm">
                                        {{ $buku->author ?: 'Tanpa penulis' }}
                                        @if ($buku->page_count)
                                            &middot; {{ $buku->page_count }} halaman
                                        @endif
                                        &middot; {{ $buku->labelAkses() }}
                                    </p>

                                    @if (($potonganIsi[$buku->id] ?? null))
                                        {{-- Buku ini muncul karena ISINYA cocok, bukan judulnya — jelaskan mengapa. --}}
                                        <p class="mt-2.5 max-w-2xl border-l-2 border-jingga-400 dark:border-jingga-500 pl-3 text-sm italic leading-relaxed text-netral-600 dark:text-netral-300">
                                            &ldquo;{{ $potonganIsi[$buku->id] }}&rdquo;
                                            <span class="not-italic text-xs font-medium text-netral-500 dark:text-netral-400">&mdash; cocok di isi buku</span>
                                        </p>
                                    @elseif ($buku->description)
                                        <p class="mt-2.5 max-w-2xl line-clamp-2 text-sm leading-relaxed text-netral-600 dark:text-netral-300">
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