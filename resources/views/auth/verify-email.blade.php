<x-guest-layout>
    @php($pesanStatus = session('status') === 'verification-link-sent'
        ? 'Tautan verifikasi baru telah dikirim ke surel Anda.'
        : session('status'))

    <x-auth-session-status class="mb-5" :status="$pesanStatus" />

    <div class="text-center">
        <h2 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">
            Verifikasi email Anda
        </h2>

        <p class="mt-3 text-sm leading-relaxed text-netral-600 dark:text-netral-400">
            Tautan verifikasi telah dikirim ke alamat email yang Anda daftarkan.
            Buka surel itu dan klik tautannya untuk mengaktifkan akses pustaka.
            Bila tidak ditemukan, periksa juga folder spam.
        </p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-7">
        @csrf
        <x-primary-button class="w-full justify-center">
            Kirim ulang tautan verifikasi
        </x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit"
                class="text-sm text-netral-500 dark:text-netral-400 underline-offset-2 hover:text-netral-900 dark:hover:text-netral-100 hover:underline transition-colors duration-150 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-jingga-500">
            Keluar dari akun ini
        </button>
    </form>
</x-guest-layout>
