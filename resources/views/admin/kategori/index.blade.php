<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Kategori</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex justify-end">
                <a href="{{ route('admin.kategori.create') }}"
                   class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                    Tambah Kategori
                </a>
            </div>

            <div class="overflow-hidden border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-netral-200 dark:divide-arang-600 text-sm">
                    <thead class="bg-netral-50 dark:bg-arang-700 text-left text-netral-600 dark:text-netral-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama Kategori</th>
                            <th class="px-4 py-3 font-medium">Lingkup</th>
                            <th class="px-4 py-3 font-medium">Jumlah Buku</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-netral-200 dark:divide-arang-600">
                        @forelse ($daftarKategori as $kategori)
                            <tr>
                                <td class="px-4 py-3 font-medium text-netral-900 dark:text-netral-50">{{ $kategori->name }}</td>
                                <td class="px-4 py-3">
                                    @if ($kategori->isUmum())
                                        <span class="rounded bg-netral-100 dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-700 dark:text-netral-300">Umum</span>
                                    @else
                                        <span class="rounded bg-jingga-50 dark:bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-700 dark:text-jingga-300">{{ $kategori->prodi->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-400">{{ $kategori->books_count }}</td>
                                <td class="px-4 py-3">
                                    @if ($kategori->bolehDikelolaOleh(auth()->user()))
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.kategori.edit', $kategori) }}"
                                               class="rounded border border-netral-200 dark:border-arang-500 px-3 py-1.5 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">
                                                Ubah
                                            </a>
                                            <x-tombol-hapus
                                                :action="route('admin.kategori.destroy', $kategori)"
                                                judul="Hapus Kategori"
                                                :pesan="'Kategori &quot;'.$kategori->name.'&quot; akan dihapus permanen.'" />
                                        </div>
                                    @else
                                        <p class="text-right text-xs text-netral-400">Dikelola dosen lain</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-netral-500 dark:text-netral-400">Belum ada kategori.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarKategori->links() }}</div>
        </div>
    </div>
</x-app-layout>