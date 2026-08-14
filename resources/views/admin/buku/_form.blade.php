{{-- Bidang isian buku. Variabel $buku boleh null saat menambah data baru. --}}
@php($buku = $buku ?? null)

<div x-data="{ mode: '{{ old('access_mode', $buku->access_mode ?? 'readonly') }}' }">

    <div>
        <x-input-label for="title" value="Judul Buku" />
        <x-text-input id="title" name="title" type="text" class="mt-1"
                      :value="old('title', $buku->title ?? '')" required autofocus maxlength="200" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="author" value="Penulis (opsional)" />
        <x-text-input id="author" name="author" type="text" class="mt-1"
                      :value="old('author', $buku->author ?? '')" maxlength="120" />
        <x-input-error :messages="$errors->get('author')" class="mt-2" />
    </div>

    <div class="mt-4">
        <x-input-label for="description" value="Deskripsi Singkat (opsional)" />
        <textarea id="description" name="description" rows="3" maxlength="2000"
                  class="mt-1 w-full rounded-sm border-sepia-700 bg-sepia-800/50 text-kabut-100 focus:border-jingga-500 focus:ring-1 focus:ring-jingga-500">{{ old('description', $buku->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    {{-- Lingkup --}}
    <fieldset class="mt-6">
        <legend class="text-sm font-semibold text-kabut-300">Lingkup Buku</legend>

        @php($lingkupTerpilih = old('lingkup', ($buku && $buku->isUmum()) ? 'umum' : 'prodi'))

        <label class="mt-3 flex cursor-pointer gap-3 border border-sepia-700 p-4 hover:bg-sepia-800/40">
            <input type="radio" name="lingkup" value="prodi" class="mt-0.5 text-jingga-600 focus:ring-jingga-500"
                   @checked($lingkupTerpilih === 'prodi') required>
            <span>
                <span class="block text-sm font-medium text-kabut-100">{{ auth()->user()->prodi?->name }}</span>
                <span class="block text-xs text-kabut-400">Hanya mahasiswa program studi Anda yang dapat melihatnya.</span>
            </span>
        </label>

        <label class="mt-2 flex cursor-pointer gap-3 border border-sepia-700 p-4 hover:bg-sepia-800/40">
            <input type="radio" name="lingkup" value="umum" class="mt-0.5 text-jingga-600 focus:ring-jingga-500"
                   @checked($lingkupTerpilih === 'umum')>
            <span>
                <span class="block text-sm font-medium text-kabut-100">Umum</span>
                <span class="block text-xs text-kabut-400">Dapat dilihat mahasiswa dari seluruh program studi.</span>
            </span>
        </label>

        <x-input-error :messages="$errors->get('lingkup')" class="mt-2" />
    </fieldset>

    <div class="mt-4">
        <x-input-label for="category_id" value="Kategori (opsional)" />
        <select id="category_id" name="category_id"
                class="mt-1 block w-full rounded-sm border-sepia-700 bg-sepia-800/50 text-kabut-100 focus:border-jingga-500 focus:ring-jingga-500">
            <option value="">— Tanpa kategori —</option>
            @foreach ($daftarKategori as $kategori)
                <option value="{{ $kategori->id }}" @selected(old('category_id', $buku->category_id ?? null) == $kategori->id)>
                    {{ $kategori->name }} ({{ $kategori->prodi?->name ?? 'Umum' }})
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>

    {{-- Berkas --}}
    <div class="mt-6 border-t border-sepia-700 pt-6">
        <x-input-label for="berkas" :value="$buku ? 'Ganti Berkas PDF (opsional)' : 'Berkas PDF'" />
        <input id="berkas" name="berkas" type="file" accept="application/pdf" @required(! $buku)
               class="mt-1 block w-full text-sm text-kabut-300 file:mr-3 file:rounded-sm file:border-0 file:bg-sepia-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-kabut-50 hover:file:bg-sepia-700">
        <p class="mt-1 text-xs text-kabut-400">Format PDF, maksimal 30 MB.</p>
        @if ($buku)
            <p class="mt-1 text-xs text-kabut-400">Berkas saat ini: {{ $buku->ukuranMb() }} MB. Kosongkan bila tidak ingin mengganti.</p>
        @endif
        <x-input-error :messages="$errors->get('berkas')" class="mt-2" />
    </div>

    <p class="mt-1 text-xs text-kabut-400">
        Jumlah halaman akan dihitung otomatis dari berkas yang diunggah.
    </p>

    <div class="mt-4">
        <x-input-label for="sampul" value="Gambar Sampul (opsional)" />
        <input id="sampul" name="sampul" type="file" accept="image/*"
               class="mt-1 block w-full text-sm text-kabut-300 file:mr-3 file:rounded-sm file:border-0 file:bg-sepia-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-kabut-100 hover:file:bg-sepia-700">
        <x-input-error :messages="$errors->get('sampul')" class="mt-2" />
    </div>

    {{-- Aturan unduh --}}
    <fieldset class="mt-6 border-t border-sepia-700 pt-6">
        <legend class="text-sm font-semibold text-kabut-300">Aturan Unduh</legend>

        <label class="mt-3 flex cursor-pointer gap-3 border border-sepia-700 p-4 hover:bg-sepia-800/40">
            <input type="radio" name="access_mode" value="full" x-model="mode" class="mt-0.5 text-jingga-600 focus:ring-jingga-500" required>
            <span>
                <span class="block text-sm font-medium text-kabut-100">Unduh penuh</span>
                <span class="block text-xs text-kabut-400">Mahasiswa boleh mengunduh seluruh isi buku.</span>
            </span>
        </label>

        <label class="mt-2 flex cursor-pointer gap-3 border border-sepia-700 p-4 hover:bg-sepia-800/40">
            <input type="radio" name="access_mode" value="partial" x-model="mode" class="mt-0.5 text-jingga-600 focus:ring-jingga-500">
            <span>
                <span class="block text-sm font-medium text-kabut-100">Unduh sebagian</span>
                <span class="block text-xs text-kabut-400">Hanya rentang halaman tertentu yang boleh diunduh.</span>
            </span>
        </label>

        <label class="mt-2 flex cursor-pointer gap-3 border border-sepia-700 p-4 hover:bg-sepia-800/40">
            <input type="radio" name="access_mode" value="readonly" x-model="mode" class="mt-0.5 text-jingga-600 focus:ring-jingga-500">
            <span>
                <span class="block text-sm font-medium text-kabut-100">Baca saja</span>
                <span class="block text-xs text-kabut-400">Buku hanya dapat dibaca langsung di situs, tidak dapat diunduh.</span>
            </span>
        </label>

        <x-input-error :messages="$errors->get('access_mode')" class="mt-2" />

        {{-- Rentang halaman hanya relevan untuk mode sebagian --}}
        <div x-show="mode === 'partial'" x-cloak class="mt-3 grid gap-4 border border-jingga-700/50 bg-jingga-900/30 p-4 sm:grid-cols-2">
            <div>
                <x-input-label for="download_page_start" value="Halaman Awal" />
                <x-text-input id="download_page_start" name="download_page_start" type="number" min="1" class="mt-1"
                              :value="old('download_page_start', $buku->download_page_start ?? '')" />
                <x-input-error :messages="$errors->get('download_page_start')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="download_page_end" value="Halaman Akhir" />
                <x-text-input id="download_page_end" name="download_page_end" type="number" min="1" class="mt-1"
                              :value="old('download_page_end', $buku->download_page_end ?? '')" />
                <x-input-error :messages="$errors->get('download_page_end')" class="mt-2" />
            </div>
        </div>
    </fieldset>

    {{-- Opsi tambahan --}}
    <div class="mt-6 space-y-3 border-t border-sepia-700 pt-6">
        <label class="flex items-start gap-3">
            <input type="hidden" name="watermark_enabled" value="0">
            <input type="checkbox" name="watermark_enabled" value="1"
                   class="mt-0.5 rounded-sm border-sepia-600 text-jingga-600 focus:ring-jingga-500"
                   @checked(old('watermark_enabled', $buku->watermark_enabled ?? true))>
            <span>
                <span class="block text-sm font-medium text-kabut-100">Aktifkan watermark</span>
                <span class="block text-xs text-kabut-400">Nama dan email mahasiswa dicantumkan pada berkas unduhan.</span>
            </span>
        </label>

        <label class="flex items-start gap-3">
            <input type="hidden" name="is_published" value="0">
            <input type="checkbox" name="is_published" value="1"
                   class="mt-0.5 rounded-sm border-sepia-600 text-jingga-600 focus:ring-jingga-500"
                   @checked(old('is_published', $buku->is_published ?? true))>
            <span>
                <span class="block text-sm font-medium text-kabut-100">Terbitkan sekarang</span>
                <span class="block text-xs text-kabut-400">Nonaktifkan bila ingin menyimpannya sebagai draf.</span>
            </span>
        </label>
    </div>
</div>