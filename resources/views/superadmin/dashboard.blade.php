<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-kabut-800">Dashboard Super Admin</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-sm border border-kabut-200 bg-white p-5">
                    <p class="text-sm text-kabut-500">Program Studi</p>
                    <p class="mt-1 text-3xl font-semibold text-kabut-900">{{ $jumlahProdi }}</p>
                </div>
                <div class="rounded-sm border border-kabut-200 bg-white p-5">
                    <p class="text-sm text-kabut-500">Dosen / Admin</p>
                    <p class="mt-1 text-3xl font-semibold text-kabut-900">{{ $jumlahDosen }}</p>
                </div>
                <div class="rounded-sm border border-kabut-200 bg-white p-5">
                    <p class="text-sm text-kabut-500">Mahasiswa</p>
                    <p class="mt-1 text-3xl font-semibold text-kabut-900">{{ $jumlahMahasiswa }}</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('superadmin.prodi.index') }}"
                   class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                    Kelola Program Studi
                </a>
                <a href="{{ route('superadmin.dosen.index') }}"
                   class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-semibold text-kabut-700 hover:bg-kabut-50">
                    Kelola Akun Dosen
                </a>
            </div>
        </div>
    </div>
</x-app-layout>