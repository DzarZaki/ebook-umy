<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">Ubah Kategori</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-kabut-200 bg-white p-6">
                <form id="form-kategori" method="POST" action="{{ route('admin.kategori.update', $kategori) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nama Kategori" />
                        <x-text-input id="name" name="name" type="text" class="mt-1"
                                      :value="old('name', $kategori->name)" required autofocus maxlength="80" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <p class="mt-4 border border-kabut-200 bg-kabut-50 p-3 text-xs text-kabut-600">
                        Lingkup kategori ini <strong>{{ $kategori->isUmum() ? 'Umum' : $kategori->prodi->name }}</strong>
                        dan tidak dapat diubah, agar buku yang sudah terkait tidak berpindah tanpa sengaja.
                    </p>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.kategori.index') }}"
                       class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-100">Batal</a>

                    <x-tombol-konfirmasi form-id="form-kategori" judul="Simpan Perubahan"
                        pesan="Perubahan nama kategori akan langsung berlaku. Lanjutkan?" label="Simpan Perubahan" label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>