<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-semibold text-netral-900 dark:text-netral-50">Mahasiswa Program Studi</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-alert-status />

        {{-- Pencarian --}}
        <form method="GET" action="{{ route('admin.mahasiswa.index') }}" class="mb-6 flex gap-2">
            <input type="text" name="cari" value="{{ $cari }}"
                   placeholder="Cari nama atau email mahasiswa"
                   class="w-full max-w-sm rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 placeholder-netral-500 dark:placeholder-netral-400 text-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-jingga-500 shadow-sm">
            <x-primary-button>Cari</x-primary-button>

            @if ($cari !== '')
                <a href="{{ route('admin.mahasiswa.index') }}"
                   class="inline-flex items-center px-3 text-sm text-netral-600 dark:text-netral-400 underline hover:text-netral-900 dark:hover:text-netral-100">
                    Bersihkan
                </a>
            @endif
        </form>

        <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg overflow-x-auto shadow-sm dark:shadow-none transition-colors">
            <table class="w-full text-sm">
                <thead class="border-b border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700 text-left text-xs uppercase tracking-wide text-netral-600 dark:text-netral-400">
                    <tr>
                        <th class="px-4 py-3">Nama Lengkap</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Terdaftar</th>
                        <th class="px-4 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-netral-200 dark:divide-arang-600">
                    @forelse ($daftarMahasiswa as $mahasiswa)
                        <tr>
                            <td class="px-4 py-3 font-medium text-netral-900 dark:text-netral-50">{{ $mahasiswa->name }}</td>
                            <td class="px-4 py-3 text-netral-600 dark:text-netral-300">{{ $mahasiswa->email }}</td>
                            <td class="px-4 py-3">
                                @if ($mahasiswa->is_active)
                                    <span class="inline-block rounded bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300">Aktif</span>
                                @else
                                    <span class="inline-block rounded bg-red-50 dark:bg-red-900/30 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-netral-500 dark:text-netral-400">{{ $mahasiswa->created_at->translatedFormat('d M Y') }}</td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.mahasiswa.edit', $mahasiswa) }}"
                                       class="text-sm font-medium text-jingga-600 dark:text-jingga-400 underline hover:text-jingga-700 dark:hover:text-jingga-300">Ubah</a>

                                    <x-tombol-konfirmasi
                                        :form-id="'form-status-'.$mahasiswa->id"
                                        :label="$mahasiswa->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                        judul="Ubah Status Akun"
                                        :pesan="'Anda akan '.($mahasiswa->is_active ? 'menonaktifkan' : 'mengaktifkan').' akun '.$mahasiswa->name.'. Lanjutkan?'" />

                                    <x-tombol-hapus
                                        :action="route('admin.mahasiswa.destroy', $mahasiswa)"
                                        judul="Hapus Akun Mahasiswa"
                                        :pesan="'Akun '.$mahasiswa->name.' akan dihapus permanen. Lanjutkan?'" />
                                </div>

                                <form id="form-status-{{ $mahasiswa->id }}" method="POST"
                                      action="{{ route('admin.mahasiswa.status', $mahasiswa) }}" class="hidden">
                                    @csrf @method('PATCH')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-netral-500 dark:text-netral-400">
                                Belum ada mahasiswa yang mendaftar dengan kode akses program studi Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $daftarMahasiswa->links() }}</div>
    </div>
</x-app-layout>