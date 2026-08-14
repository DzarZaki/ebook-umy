<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-50">
            Beranda Dosen &middot; {{ auth()->user()->prodi?->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            {{-- Kode akses pendaftaran mahasiswa --}}
            <div class="mb-8 border border-sepia-700 bg-sepia-800/50 backdrop-blur-sm p-6 rounded-lg">
                <h3 class="font-display text-lg font-semibold text-kabut-50">Kode Akses Program Studi</h3>
                <p class="mt-1 text-sm text-kabut-400">
                    Bagikan kode ini kepada mahasiswa Anda. Mereka memakainya saat mendaftar agar otomatis
                    masuk ke program studi {{ auth()->user()->prodi->name }}.
                </p>
                <form id="form-kode-akses" method="POST" action="{{ route('admin.kode-akses.update') }}"
                      class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf @method('PATCH')
                    <div>
                        <x-input-label for="access_code" value="Kode Akses" />
                        <x-text-input id="access_code" name="access_code" type="text"
                                      class="mt-1 block w-56 uppercase tracking-widest"
                                      :value="old('access_code', auth()->user()->prodi->access_code)" required />
                    </div>
                    <x-tombol-konfirmasi
    form-id="form-kode-akses"
    label="Perbarui Kode"
    judul="Perbarui Kode Akses"
    pesan="Kode lama akan berhenti berlaku. Mahasiswa yang sudah terdaftar tidak terpengaruh. Lanjutkan?" />
                </form>
                <x-input-error :messages="$errors->get('access_code')" class="mt-2" />
            </div>

            @php($prodiDosen = auth()->user()->prodi)

@if ($prodiDosen)
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border border-sepia-700 bg-sepia-800/50 backdrop-blur-sm p-5 rounded-lg">
        <div>
            <p class="font-display text-base font-semibold text-kabut-50">Sakelar unduhan {{ $prodiDosen->name }}</p>
            <p class="mt-1 text-sm text-kabut-400">
                @if ($prodiDosen->download_enabled)
                    Mahasiswa program studi Anda <strong class="text-emerald-400">dapat mengunduh</strong> sesuai aturan tiap buku.
                @else
                    Seluruh unduhan <strong class="text-red-400">sedang dinonaktifkan</strong>. Buku tetap dapat dibaca langsung di situs.
                @endif
            </p>
        </div>

        <form id="form-sakelar-unduh" method="POST" action="{{ route('admin.pengaturan-unduh.update') }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="download_enabled" value="{{ $prodiDosen->download_enabled ? 0 : 1 }}">
        </form>

        <x-tombol-konfirmasi
            form-id="form-sakelar-unduh"
            judul="Ubah Sakelar Unduhan"
            :pesan="$prodiDosen->download_enabled
                ? 'Seluruh unduhan untuk mahasiswa program studi Anda akan dimatikan. Lanjutkan?'
                : 'Mahasiswa akan kembali dapat mengunduh sesuai aturan tiap buku. Lanjutkan?'"
            :label="$prodiDosen->download_enabled ? 'Matikan Unduhan' : 'Aktifkan Unduhan'"
            label-konfirmasi="Ya, Ubah" />
    </div>
@endif

            <div class="grid gap-px border border-sepia-700 bg-sepia-700 sm:grid-cols-3">
                <div class="bg-sepia-800/50 backdrop-blur-sm p-5 rounded-tl-lg">
                    <p class="text-sm text-kabut-400">Buku prodi Anda</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-kabut-50">{{ $jumlahBukuProdi }}</p>
                </div>
                <div class="bg-sepia-800/50 backdrop-blur-sm p-5">
                    <p class="text-sm text-kabut-400">Buku umum</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-kabut-50">{{ $jumlahBukuUmum }}</p>
                </div>
                <div class="bg-sepia-800/50 backdrop-blur-sm p-5 rounded-tr-lg">
                    <p class="text-sm text-kabut-400">Kategori tersedia</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-kabut-50">{{ $jumlahKategori }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('admin.buku.create') }}"
                   class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-500">
                    Unggah Buku Baru
                </a>
                <a href="{{ route('admin.kategori.index') }}"
                   class="rounded-sm border border-sepia-600 bg-sepia-800 px-4 py-2 text-sm font-semibold text-kabut-300 hover:bg-sepia-700">
                    Kelola Kategori
                </a>
            </div>

            <h3 class="mt-10 font-display text-lg font-semibold text-kabut-50">Unggahan terbaru</h3>

            <ul class="mt-3 divide-y divide-sepia-700 border border-sepia-700 bg-sepia-800/50 backdrop-blur-sm rounded-lg overflow-hidden">
                @forelse ($bukuTerbaru as $buku)
                    <li class="flex items-center justify-between px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-kabut-50">{{ $buku->title }}</p>
                            <p class="text-xs text-kabut-400">
                                {{ $buku->prodi?->name ?? 'Umum' }} &middot; {{ $buku->labelAkses() }}
                            </p>
                        </div>
                        <span class="text-xs text-kabut-500">{{ $buku->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-kabut-400">Belum ada buku yang diunggah.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>