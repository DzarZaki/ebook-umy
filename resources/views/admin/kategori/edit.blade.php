<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Ubah Kategori</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-kategori" method="POST" action="{{ route('admin.kategori.update', $kategori) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nama Kategori" />
                        <x-text-input id="name" name="name" type="text" class="mt-1"
                                      :value="old('name', $kategori->name)" required autofocus maxlength="80" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <p class="mt-4 border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 p-3 text-xs text-netral-600 dark:text-netral-300 rounded">
                        Lingkup kategori ini <strong>{{ $kategori->isUmum() ? 'Umum' : $kategori->prodi->name }}</strong>
                        dan tidak dapat diubah, agar buku yang sudah terkait tidak berpindah tanpa sengaja.
                    </p>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.kategori.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">Batal</a>

                    <x-tombol-konfirmasi form-id="form-kategori" judul="Simpan Perubahan"
                        pesan="Perubahan nama kategori akan langsung berlaku. Lanjutkan?" label="Simpan Perubahan" label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>