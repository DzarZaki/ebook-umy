<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Dashboard Super Admin</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 p-5 shadow-sm dark:shadow-none transition-colors">
                    <p class="text-sm text-netral-500 dark:text-netral-400">Program Studi</p>
                    <p class="mt-1 text-3xl font-semibold text-netral-900 dark:text-netral-50">{{ $jumlahProdi }}</p>
                </div>
                <div class="rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 p-5 shadow-sm dark:shadow-none transition-colors">
                    <p class="text-sm text-netral-500 dark:text-netral-400">Dosen / Admin</p>
                    <p class="mt-1 text-3xl font-semibold text-netral-900 dark:text-netral-50">{{ $jumlahDosen }}</p>
                </div>
                <div class="rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 p-5 shadow-sm dark:shadow-none transition-colors">
                    <p class="text-sm text-netral-500 dark:text-netral-400">Mahasiswa</p>
                    <p class="mt-1 text-3xl font-semibold text-netral-900 dark:text-netral-50">{{ $jumlahMahasiswa }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('superadmin.prodi.index') }}"
                   class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                    Kelola Program Studi
                </a>
                <a href="{{ route('superadmin.dosen.index') }}"
                   class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-4 py-2 text-sm font-semibold text-netral-700 dark:text-netral-300 hover:bg-netral-50 dark:hover:bg-arang-600 shadow-sm transition-colors">
                    Kelola Akun Dosen
                </a>
            </div>
        </div>
    </div>
</x-app-layout>