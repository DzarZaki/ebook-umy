<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-kabut-800">Tambah Akun Dosen</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="rounded-sm border border-kabut-200 bg-white p-6">
                <form id="form-dosen" method="POST" action="{{ route('superadmin.dosen.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nama Dosen" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="email" value="Email Kampus" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                      :value="old('email')" required placeholder="nama@umy.ac.id" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="prodi_id" value="Program Studi" />
                        <select id="prodi_id" name="prodi_id" required
                                class="mt-1 block w-full rounded-sm border-kabut-300 shadow-sm focus:border-jingga-500 focus:ring-jingga-500">
                            <option value="">— Pilih program studi —</option>
                            @foreach ($daftarProdi as $prodi)
                                <option value="{{ $prodi->id }}" @selected(old('prodi_id') == $prodi->id)>
                                    {{ $prodi->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('prodi_id')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password" value="Kata Sandi" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                                      required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                      class="mt-1 block w-full" required autocomplete="new-password" />
                    </div>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('superadmin.dosen.index') }}"
                       class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                        Batal
                    </a>

                    <x-tombol-konfirmasi
                        form-id="form-dosen"
                        judul="Buat Akun Dosen"
                        pesan="Akun dosen baru akan dibuat dan langsung aktif. Lanjutkan?"
                        label="Simpan"
                        label-konfirmasi="Ya, Buat Akun" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>