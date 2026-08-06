<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">Katalog Bacaan</h2>
                <p class="mt-1 text-sm text-kabut-500">
                    @if (auth()->user()->isSuperAdmin())
                        Menampilkan seluruh koleksi dari semua program studi.
                    @else
                        Koleksi {{ auth()->user()->prodi?->name ?? 'program studi Anda' }} beserta bacaan umum.
                    @endif
                </p>
            </div>
            <p class="text-sm text-kabut-500">{{ $daftarBuku->total() }} judul ditemukan</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Panel penyaring: form GET biasa agar tetap berfungsi tanpa JavaScript --}}
            <form method="GET" action="{{ route('katalog.index') }}"
                  class="grid gap-4 border border-kabut-200 bg-white p-5 lg:grid-cols-12">

                <div class="lg:col-span-5">
                    <x-input-label for="q" value="Cari judul, penulis, atau deskripsi" />
                    <x-text-input id="q" name="q" type="search" class="mt-1" :value="$kueri"
                                  placeholder="mis. manajemen operasional" />
                </div>

                <div class="lg:col-span-3">
                    <x-input-label for="kategori" value="Kategori" />
                    <select id="kategori" name="kategori"
                            class="mt-1 block w-full rounded-sm border-kabut-300 focus:border-jingga-500 focus:ring-jingga-500">
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
                            class="mt-1 block w-full rounded-sm border-kabut-300 focus:border-jingga-500 focus:ring-jingga-500">
                        <option value="semua" @selected($lingkup === 'semua')>Semua</option>
                        <option value="prodi" @selected($lingkup === 'prodi')>Program studi</option>
                        <option value="umum" @selected($lingkup === 'umum')>Umum</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <x-input-label for="urut" value="Urutkan" />
                    <select id="urut" name="urut"
                            class="mt-1 block w-full rounded-sm border-kabut-300 focus:border-jingga-500 focus:ring-jingga-500">
                        <option value="terbaru" @selected($urut === 'terbaru')>Terbaru</option>
                        <option value="judul" @selected($urut === 'judul')>Judul A–Z</option>
                    </select>
                </div>

                <div class="flex items-center gap-3 lg:col-span-12">
                    <button type="submit"
                            class="rounded-sm bg-jingga-600 px-5 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                        Terapkan
                    </button>

                    @if ($kueri || $kategoriId || $lingkup !== 'semua' || $urut !== 'terbaru')
                        <a href="{{ route('katalog.index') }}" class="text-sm font-medium text-kabut-600 underline underline-offset-4 hover:text-kabut-900">
                            Hapus penyaring
                        </a>
                    @endif
                </div>
            </form>

            {{-- Daftar buku --}}
            @if ($daftarBuku->isEmpty())
                <div class="mt-6 border border-dashed border-kabut-300 bg-white px-6 py-16 text-center">
                    <p class="font-display text-lg font-semibold text-kabut-800">Tidak ada buku yang cocok</p>
                    <p class="mt-2 text-sm text-kabut-500">
                        @if ($kueri)
                            Tidak ditemukan hasil untuk &ldquo;{{ $kueri }}&rdquo;. Coba kata kunci lain atau hapus penyaringnya.
                        @else
                            Dosen pengampu belum menambahkan bacaan pada bagian ini.
                        @endif
                    </p>
                </div>
            @else
                <div class="mt-6 grid gap-px border border-kabut-200 bg-kabut-200 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($daftarBuku as $buku)
                        <article class="flex gap-4 bg-white p-5">
                            {{-- Sampul, atau kotak inisial bila tidak ada gambar --}}
                            <a href="{{ route('katalog.show', $buku) }}" class="shrink-0">
                                @if ($buku->coverUrl())
                                    <img src="{{ $buku->coverUrl() }}" alt="Sampul {{ $buku->title }}"
                                         class="h-28 w-20 border border-kabut-200 object-cover">
                                @else
                                    <span class="flex h-28 w-20 items-center justify-center border border-sepia-800 bg-sepia-800 font-display text-xl font-semibold text-kabut-50">
                                        {{ $buku->inisial() }}
                                    </span>
                                @endif
                            </a>

                            <div class="min-w-0">
                                <div class="flex flex-wrap gap-1.5">
                                    @if ($buku->isUmum())
                                        <span class="rounded-sm bg-sepia-100 px-2 py-0.5 text-[11px] font-medium text-sepia-800">Umum</span>
                                    @else
                                        <span class="rounded-sm bg-jingga-50 px-2 py-0.5 text-[11px] font-medium text-jingga-800">{{ $buku->prodi->name }}</span>
                                    @endif

                                    @if ($buku->category)
                                        <span class="rounded-sm bg-kabut-100 px-2 py-0.5 text-[11px] font-medium text-kabut-600">{{ $buku->category->name }}</span>
                                    @endif
                                </div>

                                <h3 class="mt-2 font-display text-base font-semibold leading-snug text-kabut-900">
                                    <a href="{{ route('katalog.show', $buku) }}" class="hover:text-jingga-700">{{ $buku->title }}</a>
                                </h3>

                                <p class="mt-1 text-xs text-kabut-500">{{ $buku->author ?? 'Tanpa penulis' }}</p>

                                @if ($buku->description)
                                    <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-kabut-600">{{ $buku->description }}</p>
                                @endif

                                <p class="mt-3 text-xs font-medium text-kabut-500">{{ $buku->labelAkses() }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">{{ $daftarBuku->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>