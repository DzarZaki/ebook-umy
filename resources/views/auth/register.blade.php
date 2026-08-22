<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-netral-900 dark:text-netral-100">Buat Akun Mahasiswa</h1>
        <p class="mt-1 text-sm text-netral-500 dark:text-netral-300">
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
            <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">
                Tuliskan nama asli sesuai data akademik, minimal dua kata.
            </p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- Email --}}
        <div class="mt-4">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                          :value="old('email')" required autocomplete="username" />
            <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">
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
            <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">
                Kode ini menentukan program studi Anda dan hanya dapat diubah oleh dosen.
            </p>
            <x-input-error :messages="$errors->get('kode_akses')" class="mt-2" />
        </div>

        {{-- Kata sandi --}}
        <div class="mt-4">
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          required autocomplete="new-password" />
            <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">
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
            <a href="{{ route('login') }}" class="text-sm text-netral-600 dark:text-netral-300 underline hover:text-netral-900 dark:hover:text-netral-100">
                Sudah punya akun?
            </a>

            <x-primary-button>Daftar</x-primary-button>
        </div>

        {{-- Modal peringatan bahwa program studi bersifat menetap --}}
        <div x-show="konfirmasi" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-arang-900/60 backdrop-blur-sm px-4">
            <div class="w-full max-w-md border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 rounded-lg shadow-xl" @click.outside="konfirmasi = false">
                <h2 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-100">Periksa sekali lagi</h2>

                <p class="mt-2 text-sm text-netral-600 dark:text-netral-300">
                    Program studi Anda ditentukan oleh kode akses yang dimasukkan dan
                    <strong>tidak dapat Anda ubah sendiri</strong> setelah akun dibuat.
                    Perubahan hanya bisa dilakukan oleh dosen program studi.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="konfirmasi = false"
                            class="rounded border border-netral-200 dark:border-arang-600 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 transition-colors hover:bg-netral-100 dark:hover:bg-arang-700">
                        Periksa Ulang
                    </button>

                    <button type="button" @click="$el.closest('form').submit()"
                            class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm">
                        Ya, Daftarkan Saya
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-guest-layout>