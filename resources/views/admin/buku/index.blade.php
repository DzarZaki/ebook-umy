<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">Koleksi Buku</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex justify-end">
                <a href="{{ route('admin.buku.create') }}"
                   class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                    Unggah Buku
                </a>
            </div>

            <div class="overflow-hidden border border-kabut-200 bg-white">
                <table class="min-w-full divide-y divide-kabut-200 text-sm">
                    <thead class="bg-kabut-100 text-left text-kabut-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium">Lingkup</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Akses Unduh</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-kabut-100">
                        @forelse ($daftarBuku as $buku)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-kabut-900">{{ $buku->title }}</p>
                                    <p class="text-xs text-kabut-500">{{ $buku->author ?? 'Tanpa penulis' }} &middot; {{ $buku->ukuranMb() }} MB</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->isUmum())
                                        <span class="rounded-sm bg-sepia-100 px-2 py-1 text-xs font-medium text-sepia-800">Umum</span>
                                    @else
                                        <span class="rounded-sm bg-jingga-50 px-2 py-1 text-xs font-medium text-jingga-800">{{ $buku->prodi->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-kabut-600">{{ $buku->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-kabut-600">{{ $buku->labelAkses() }}</td>
                                <td class="px-4 py-3">
                                    @if ($buku->is_published)
                                        <span class="rounded-sm bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-800">Terbit</span>
                                    @else
                                        <span class="rounded-sm bg-kabut-100 px-2 py-1 text-xs font-medium text-kabut-600">Draf</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->bolehDikelolaOleh(auth()->user()))
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.buku.edit', $buku) }}"
                                               class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-100">Ubah</a>
                                            <x-tombol-hapus
                                                :action="route('admin.buku.destroy', $buku)"
                                                judul="Hapus Buku"
                                                :pesan="'Buku &quot;'.$buku->title.'&quot; beserta berkas PDF-nya akan dihapus permanen.'" />
                                        </div>
                                    @else
                                        <p class="text-right text-xs text-kabut-400">Dikelola dosen lain</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-kabut-500">Belum ada buku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarBuku->links() }}</div>
        </div>
    </div>
</x-app-layout>