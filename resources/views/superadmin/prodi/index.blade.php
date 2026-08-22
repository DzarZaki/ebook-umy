<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Program Studi</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex justify-end">
                <a href="{{ route('superadmin.prodi.create') }}"
                   class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                    Tambah Program Studi
                </a>
            </div>

            <div class="overflow-hidden rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-netral-200 dark:divide-arang-600 text-sm">
                    <thead class="bg-netral-50 dark:bg-arang-700 text-left text-netral-600 dark:text-netral-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama Program Studi</th>
                            <th class="px-4 py-3 font-medium">Slug</th>
                            <th class="px-4 py-3 font-medium">Pengguna</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-netral-200 dark:divide-arang-600">
                        @forelse ($daftarProdi as $prodi)
                            <tr>
                                <td class="px-4 py-3 font-medium text-netral-900 dark:text-netral-50">{{ $prodi->name }}</td>
                                <td class="px-4 py-3 text-netral-500 dark:text-netral-400">{{ $prodi->slug }}</td>
                                <td class="px-4 py-3 text-netral-700 dark:text-netral-300">{{ $prodi->users_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('superadmin.prodi.edit', $prodi) }}"
                                           class="rounded border border-netral-200 dark:border-arang-500 px-3 py-1.5 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">
                                            Ubah
                                        </a>

                                        <x-tombol-hapus
                                            :action="route('superadmin.prodi.destroy', $prodi)"
                                            judul="Hapus Program Studi"
                                            :pesan="'Program studi &quot;'.$prodi->name.'&quot; akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.'" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-netral-500 dark:text-netral-400">
                                    Belum ada program studi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarProdi->links() }}</div>
        </div>
    </div>
</x-app-layout>