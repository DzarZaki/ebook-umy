<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Ubah Akun Dosen</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-dosen" method="POST" action="{{ route('superadmin.dosen.update', $dosen) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nama Dosen" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name', $dosen->name)" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="email" value="Email Kampus" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email', $dosen->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="prodi_id" value="Program Studi" />
                        <select id="prodi_id" name="prodi_id" required
                                class="mt-1 block w-full rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 shadow-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-jingga-500">
                            @foreach ($daftarProdi as $prodi)
                                <option value="{{ $prodi->id }}" @selected(old('prodi_id', $dosen->prodi_id) == $prodi->id)>
                                    {{ $prodi->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('prodi_id')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="rounded border-netral-300 dark:border-arang-500 text-jingga-600 focus:ring-jingga-500"
                                   @checked(old('is_active', $dosen->is_active))>
                            <span class="text-sm text-netral-700 dark:text-netral-300">Akun aktif</span>
                        </label>
                    </div>

                    <div class="mt-6 border-t border-netral-200 dark:border-arang-600 pt-6">
                        <p class="text-sm text-netral-500 dark:text-netral-400">Kosongkan bagian di bawah bila tidak ingin mengganti kata sandi.</p>

                        <div class="mt-3">
                            <x-input-label for="password" value="Kata Sandi Baru" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                          autocomplete="new-password" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                          class="mt-1 block w-full" autocomplete="new-password" />
                        </div>
                    </div>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('superadmin.dosen.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">
                        Batal
                    </a>

                    <x-tombol-konfirmasi
                        form-id="form-dosen"
                        judul="Simpan Perubahan"
                        pesan="Perubahan data dosen akan langsung berlaku. Lanjutkan?"
                        label="Simpan Perubahan"
                        label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>