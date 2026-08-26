<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Akun Dosen</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex justify-end">
                <a href="{{ route('superadmin.dosen.create') }}"
                   class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                    Tambah Dosen
                </a>
            </div>

            <div class="overflow-x-auto rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-netral-200 dark:divide-arang-600 text-sm">
                    <thead class="bg-netral-50 dark:bg-arang-700 text-left text-netral-600 dark:text-netral-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama</th>
                            <th class="px-4 py-3 font-medium">Email</th>
                            <th class="px-4 py-3 font-medium">Program Studi</th>
                            <th class="px-4 py-3 font-medium">Buku</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-netral-200 dark:divide-arang-600">
                        @forelse ($daftarDosen as $dosen)
                            @php($jumlahBuku = (int) ($dosen->buku_diunggah_count ?? 0))
                            <tr>
                                <td class="px-4 py-3 font-medium text-netral-900 dark:text-netral-50">{{ $dosen->name }}</td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-300">{{ $dosen->email }}</td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-300">{{ $dosen->prodi?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-300">
                                    @if ($jumlahBuku > 0)
                                        {{ $jumlahBuku }} buku
                                    @else
                                        <span class="text-netral-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($dosen->is_active)
                                        <span class="rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-netral-100 dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-600 dark:text-netral-400">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('superadmin.dosen.edit', $dosen) }}"
                                           class="rounded border border-netral-200 dark:border-arang-500 px-3 py-1.5 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">
                                            Ubah
                                        </a>

                                        @if ($jumlahBuku > 0)
                                            <span class="cursor-not-allowed rounded border border-netral-200 dark:border-arang-600 px-3 py-1.5 text-sm font-medium text-netral-400"
                                                  title="Masih tercatat sebagai pengunggah {{ $jumlahBuku }} buku. Nonaktifkan akunnya lewat tombol Ubah.">
                                                Hapus
                                            </span>
                                        @else
                                            <x-tombol-hapus
                                                :action="route('superadmin.dosen.destroy', $dosen)"
                                                judul="Hapus Akun Dosen"
                                                :pesan="'Akun “'.$dosen->name.'” akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.'" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-netral-500 dark:text-netral-400">
                                    Belum ada akun dosen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarDosen->links() }}</div>
        </div>
    </div>
</x-app-layout>