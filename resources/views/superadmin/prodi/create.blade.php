<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Tambah Program Studi</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-prodi" method="POST" action="{{ route('superadmin.prodi.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nama Program Studi" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name')" required autofocus maxlength="100" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('superadmin.prodi.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">
                        Batal
                    </a>

                    <x-tombol-konfirmasi
                        form-id="form-prodi"
                        judul="Simpan Program Studi"
                        pesan="Program studi baru akan ditambahkan ke sistem. Lanjutkan?"
                        label="Simpan"
                        label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>