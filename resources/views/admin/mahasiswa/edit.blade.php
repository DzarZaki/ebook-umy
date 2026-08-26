<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Ubah Data Mahasiswa</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-mahasiswa" method="POST" action="{{ route('admin.mahasiswa.update', $mahasiswa) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nama Lengkap" />
                        <x-text-input id="name" name="name" type="text" class="mt-1"
                                      :value="old('name', $mahasiswa->name)" required autofocus maxlength="120" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">Minimal dua kata, hanya huruf, spasi, titik, apostrof, dan tanda hubung.</p>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1"
                                      :value="old('email', $mahasiswa->email)" required maxlength="150" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="prodi_id" value="Program Studi" />
                        <select id="prodi_id" name="prodi_id" required
                                class="mt-1 block w-full rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 text-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-jingga-500 shadow-sm">
                            @foreach ($daftarProdi as $prodi)
                                <option value="{{ $prodi->id }}" @selected(old('prodi_id', $mahasiswa->prodi_id) == $prodi->id)>
                                    {{ $prodi->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('prodi_id')" class="mt-2" />
                    </div>

                    <p class="mt-4 border border-amber-200 dark:border-amber-700/50 bg-amber-50 dark:bg-amber-900/30 p-3 text-xs text-amber-800 dark:text-amber-300 rounded">
                        Memindahkan mahasiswa ke program studi lain akan <strong>mencabut akses Anda</strong> atas akun ini,
                        dan koleksi buku yang tampil untuknya ikut berubah mengikuti prodi barunya.
                    </p>

                    <p class="mt-3 text-xs text-netral-500 dark:text-netral-400">
                        Status aktif/nonaktif dan kata sandi tidak diatur dari halaman ini.
                        Status diubah lewat tombol pada daftar mahasiswa; kata sandi hanya dapat diganti oleh pemilik akun.
                    </p>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.mahasiswa.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">Batal</a>

                    <x-tombol-konfirmasi form-id="form-mahasiswa" judul="Simpan Perubahan"
                        pesan="Perubahan data mahasiswa akan langsung berlaku. Lanjutkan?"
                        label="Simpan Perubahan" label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>