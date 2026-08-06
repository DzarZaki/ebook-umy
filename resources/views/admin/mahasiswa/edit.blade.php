<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-kabut-900">Ubah Data Mahasiswa</h2>
    </x-slot>

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-alert-status />

        <form id="form-mahasiswa" method="POST" action="{{ route('admin.mahasiswa.update', $mahasiswa) }}"
              class="border border-kabut-200 bg-white p-6">
            @csrf @method('PUT')

            <div>
                <x-input-label for="name" value="Nama Lengkap" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $mahasiswa->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                              :value="old('email', $mahasiswa->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="prodi_id" value="Program Studi" />
                <select id="prodi_id" name="prodi_id" required
                        class="mt-1 block w-full rounded-sm border-kabut-300 text-sm focus:border-jingga-500 focus:ring-jingga-500">
                    @foreach ($daftarProdi as $prodi)
                        <option value="{{ $prodi->id }}" @selected(old('prodi_id', $mahasiswa->prodi_id) == $prodi->id)>
                            {{ $prodi->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-kabut-500">
                    Memindahkan mahasiswa ke prodi lain akan mengubah koleksi buku yang dapat diaksesnya.
                </p>
                <x-input-error :messages="$errors->get('prodi_id')" class="mt-2" />
            </div>

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('admin.mahasiswa.index') }}"
                   class="text-sm text-kabut-600 underline hover:text-kabut-900">Kembali</a>

                <x-tombol-konfirmasi
    form-id="form-mahasiswa"
    label="Simpan Perubahan"
    judul="Simpan Data Mahasiswa"
    pesan="Perubahan data mahasiswa akan disimpan. Lanjutkan?" />
            </div>
        </form>
    </div>
</x-app-layout>