<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-kabut-900">Buat Akun Mahasiswa</h1>
        <p class="mt-1 text-sm text-kabut-500">
            Masukkan kode akses yang diberikan dosen program studi Anda.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}"
          x-data="{ konfirmasi: false }"
          @submit.prevent="konfirmasi = true">
        @csrf

        {{-- Nama lengkap --}}
        <div>
            <x-input-label for="name" value="Nama Lengkap" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name')" required autofocus autocomplete="name" />
            <p class="mt-1 text-xs text-kabut-500">
                Tuliskan nama asli sesuai data kampus, minimal dua kata.
            </p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autocomplete="username" />
            <p class="mt-1 text-xs text-kabut-500">
                Boleh memakai email pribadi, misalnya Gmail.
            </p>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Kode akses prodi --}}
        <div class="mt-4">
            <x-input-label for="kode_akses" value="Kode Akses Program Studi" />
            <x-text-input id="kode_akses" name="kode_akses" type="text"
                          class="mt-1 block w-full uppercase tracking-wide"
                          :value="old('kode_akses')" required />
            <p class="mt-1 text-xs text-kabut-500">
                Kode ini menentukan program studi Anda dan hanya dapat diubah oleh dosen.
            </p>
            <x-input-error :messages="$errors->get('kode_akses')" class="mt-2" />
        </div>

        {{-- Kata sandi --}}
        <div class="mt-4">
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="new-password" />
            <p class="mt-1 text-xs text-kabut-500">
                Minimal 8 karakter, gabungan huruf dan angka.
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Ulangi kata sandi --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Ulangi Kata Sandi" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <a href="{{ route('login') }}" class="text-sm text-kabut-600 underline hover:text-kabut-900">
                Sudah punya akun?
            </a>

            <x-primary-button>Daftar</x-primary-button>
        </div>

        {{-- Modal peringatan bahwa program studi bersifat menetap --}}
        <div x-show="konfirmasi" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-sepia-900/50 px-4">
            <div class="w-full max-w-md border border-kabut-200 bg-white p-6 shadow-lg" @click.outside="konfirmasi = false">
                <h2 class="font-display text-lg font-semibold text-kabut-900">Periksa sekali lagi</h2>

                <p class="mt-2 text-sm text-kabut-600">
                    Program studi Anda ditentukan oleh kode akses yang dimasukkan dan
                    <strong>tidak dapat Anda ubah sendiri</strong> setelah akun dibuat.
                    Perubahan hanya bisa dilakukan oleh dosen program studi.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="konfirmasi = false"
                            class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 transition-colors hover:bg-kabut-50">
                        Periksa Ulang
                    </button>

                    <button type="button" @click="$el.closest('form').submit()"
                            class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-jingga-700">
                        Ya, Daftarkan Saya
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-guest-layout>