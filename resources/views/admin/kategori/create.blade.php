<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Tambah Kategori</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-kategori" method="POST" action="{{ route('admin.kategori.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nama Kategori" />
                        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name')"
                                      required autofocus maxlength="80" placeholder="mis. Modul Pelajaran" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <fieldset class="mt-6">
                        <legend class="text-sm font-semibold text-netral-700 dark:text-netral-300">Lingkup Kategori</legend>

                        <label class="mt-3 flex cursor-pointer gap-3 border border-netral-200 dark:border-arang-600 rounded-lg p-4 bg-white/60 dark:bg-arang-700/30 hover:bg-netral-50 dark:hover:bg-arang-700/60 shadow-sm transition-colors">
                            <input type="radio" name="lingkup" value="prodi" class="mt-0.5 text-jingga-600 focus:ring-jingga-500"
                                   @checked(old('lingkup', 'prodi') === 'prodi') required>
                            <span>
                                <span class="block text-sm font-medium text-netral-900 dark:text-netral-100">{{ auth()->user()->prodi?->name }}</span>
                                <span class="block text-xs text-netral-500 dark:text-netral-400">Hanya tampil bagi mahasiswa program studi Anda.</span>
                            </span>
                        </label>

                        <label class="mt-2 flex cursor-pointer gap-3 border border-netral-200 dark:border-arang-600 rounded-lg p-4 bg-white/60 dark:bg-arang-700/30 hover:bg-netral-50 dark:hover:bg-arang-700/60 shadow-sm transition-colors">
                            <input type="radio" name="lingkup" value="umum" class="mt-0.5 text-jingga-600 focus:ring-jingga-500"
                                   @checked(old('lingkup') === 'umum')>
                            <span>
                                <span class="block text-sm font-medium text-netral-900 dark:text-netral-100">Umum</span>
                                <span class="block text-xs text-netral-500 dark:text-netral-400">Tampil bagi mahasiswa dari seluruh program studi.</span>
                            </span>
                        </label>

                        <x-input-error :messages="$errors->get('lingkup')" class="mt-2" />
                    </fieldset>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.kategori.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">Batal</a>

                    <x-tombol-konfirmasi form-id="form-kategori" judul="Simpan Kategori"
                        pesan="Kategori baru akan ditambahkan. Lanjutkan?" label="Simpan" label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>