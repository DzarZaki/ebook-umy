<section>
    <header>
        <h2 class="text-lg font-medium text-netral-900 dark:text-netral-50">
            Informasi Profil
        </h2>

        <p class="mt-1 text-sm text-netral-500 dark:text-netral-400">
            Perbarui informasi profil dan alamat surel akun Anda.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Surel" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        {{-- Program studi hanya ditampilkan, tidak dapat diubah pengguna sendiri. --}}
@if ($user->prodi)
    <div>
        <x-input-label value="Program Studi" />

        <div class="mt-1 border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700 px-3 py-2 text-sm text-netral-700 dark:text-netral-300 rounded">
            {{ $user->prodi->name }}
        </div>

        <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">
            @if ($user->isMahasiswa())
                Program studi ditentukan oleh kode akses saat pendaftaran dan hanya dapat diubah oleh dosen.
            @else
                Program studi akun dosen hanya dapat diubah oleh Super Admin.
            @endif
        </p>
    </div>
@endif

        {{-- Langganan pemberitahuan buku baru — hanya bermakna bagi mahasiswa. --}}
        @if ($user->isMahasiswa())
            <div class="flex items-start gap-3">
                <input id="notifikasi_buku_baru" name="notifikasi_buku_baru" type="checkbox" value="1"
                       class="mt-1 rounded border-netral-300 dark:border-arang-500 bg-white dark:bg-arang-700 text-jingga-600 focus:ring-jingga-500"
                       @checked(old('notifikasi_buku_baru', $user->notifikasi_buku_baru)) />
                <label for="notifikasi_buku_baru" class="text-sm leading-relaxed text-netral-600 dark:text-netral-300">
                    Kirimi saya surel saat ada buku baru di program studi saya
                    <span class="mt-0.5 block text-xs text-netral-500 dark:text-netral-400">
                        Satu surel per buku yang diterbitkan. Dapat dimatikan kapan saja dari halaman ini.
                    </span>
                </label>
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>Simpan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-netral-500 dark:text-netral-400"
                >Tersimpan.</p>
            @endif
        </div>
    </form>
</section>
