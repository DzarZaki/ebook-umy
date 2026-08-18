<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">Ubah Data Mahasiswa</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-kabut-200 bg-white p-6">
                <form id="form-mahasiswa" method="POST" action="{{ route('admin.mahasiswa.update', $mahasiswa) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Nama Lengkap" />
                        <x-text-input id="name" name="name" type="text" class="mt-1"
                                      :value="old('name', $mahasiswa->name)" required autofocus maxlength="120" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        <p class="mt-1 text-xs text-kabut-500">Minimal dua kata, hanya huruf, spasi, titik, apostrof, dan tanda hubung.</p>
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
                                class="mt-1 block w-full rounded-sm border-kabut-300 text-sm focus:border-jingga-500 focus:ring-jingga-500">
                            @foreach ($daftarProdi as $prodi)
                                <option value="{{ $prodi->id }}" @selected(old('prodi_id', $mahasiswa->prodi_id) == $prodi->id)>
                                    {{ $prodi->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('prodi_id')" class="mt-2" />
                    </div>

                    <p class="mt-4 border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                        Memindahkan mahasiswa ke program studi lain akan <strong>mencabut akses Anda</strong> atas akun ini,
                        dan koleksi buku yang tampil untuknya ikut berubah mengikuti prodi barunya.
                    </p>

                    <p class="mt-3 text-xs text-kabut-500">
                        Status aktif/nonaktif dan kata sandi tidak diatur dari halaman ini.
                        Status diubah lewat tombol pada daftar mahasiswa; kata sandi hanya dapat diganti oleh pemilik akun.
                    </p>
                </form>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.mahasiswa.index') }}"
                       class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-100">Batal</a>

                    <x-tombol-konfirmasi form-id="form-mahasiswa" judul="Simpan Perubahan"
                        pesan="Perubahan data mahasiswa akan langsung berlaku. Lanjutkan?"
                        label="Simpan Perubahan" label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>