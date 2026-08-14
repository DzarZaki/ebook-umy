<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-50">Koleksi Buku</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex items-center justify-end gap-2">
                <a href="{{ route('admin.buku-sampah.index') }}"
                   class="rounded-sm border border-sepia-600 px-4 py-2 text-sm font-semibold text-kabut-300 hover:bg-sepia-800/40 hover:text-kabut-100">
                    Tempat Sampah
                </a>
                @can('create', App\Models\Book::class)
                    <a href="{{ route('admin.buku.create') }}"
                       class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                        Unggah Buku
                    </a>
                @endcan
            </div>

            <div class="overflow-hidden border border-sepia-700 bg-sepia-800/50">
                <table class="min-w-full divide-y divide-sepia-700 text-sm">
                    <thead class="bg-sepia-800 text-left text-kabut-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium">Lingkup</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Akses Unduh</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sepia-700/50">
                        @forelse ($daftarBuku as $buku)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-kabut-50">{{ $buku->title }}</p>
                                    <p class="text-xs text-kabut-400">{{ $buku->author ?? 'Tanpa penulis' }} &middot; {{ $buku->ukuranMb() }} MB</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->isUmum())
                                        <span class="rounded-sm bg-sepia-800 px-2 py-1 text-xs font-medium text-kabut-300">Umum</span>
                                    @else
                                        <span class="rounded-sm bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-300">{{ $buku->prodi->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-kabut-400">{{ $buku->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-kabut-400">{{ $buku->labelAkses() }}</td>
                                <td class="px-4 py-3">
                                    @if ($buku->is_published)
                                        <span class="rounded-sm bg-emerald-900/30 px-2 py-1 text-xs font-medium text-emerald-300">Terbit</span>
                                    @else
                                        <span class="rounded-sm bg-sepia-800 px-2 py-1 text-xs font-medium text-kabut-400">Draf</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @canany(['update', 'delete'], $buku)
                                        <div class="flex items-center justify-end gap-2">
                                            @can('update', $buku)
                                                <a href="{{ route('admin.buku.edit', $buku) }}"
                                                   class="rounded-sm border border-sepia-600 px-3 py-1.5 text-sm font-medium text-kabut-300 hover:bg-sepia-800/40 hover:text-kabut-100">Ubah</a>
                                            @endcan

                                            @can('delete', $buku)
                                                <x-tombol-hapus
                                                    :action="route('admin.buku.destroy', $buku)"
                                                    judul="Buang ke Tempat Sampah"
                                                    :pesan="'Buku “'.$buku->title.'” akan dipindahkan ke Tempat Sampah. Berkas PDF dan sampulnya tetap tersimpan, dan buku masih dapat dipulihkan selama masa tenggang.'" />
                                            @endcan
                                        </div>
                                    @else
                                        <p class="text-right text-xs text-kabut-400">Dikelola dosen lain</p>
                                    @endcanany
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-kabut-400">Belum ada buku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarBuku->links() }}</div>
        </div>
    </div>
</x-app-layout>