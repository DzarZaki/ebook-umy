<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Koleksi Buku</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex items-center justify-end gap-2">
                <a href="{{ route('admin.buku-sampah.index') }}"
                   class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-4 py-2 text-sm font-semibold text-netral-700 dark:text-netral-300 hover:bg-netral-50 dark:hover:bg-arang-600 shadow-sm transition-colors">
                    Tempat Sampah
                </a>
                @can('create', App\Models\Book::class)
                    <a href="{{ route('admin.buku.create') }}"
                       class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                        Unggah Buku
                    </a>
                @endcan
            </div>

            <div class="overflow-x-auto border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-netral-200 dark:divide-arang-600 text-sm">
                    <thead class="bg-netral-50 dark:bg-arang-700 text-left text-netral-600 dark:text-netral-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium">Lingkup</th>
                            <th class="px-4 py-3 font-medium">Kategori</th>
                            <th class="px-4 py-3 font-medium">Akses Unduh</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-netral-200 dark:divide-arang-600/50">
                        @forelse ($daftarBuku as $buku)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-netral-900 dark:text-netral-50">{{ $buku->title }}</p>
                                    <p class="text-xs text-netral-500 dark:text-netral-400">{{ $buku->author ?? 'Tanpa penulis' }} &middot; {{ $buku->ukuranMb() }} MB</p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->isUmum())
                                        <span class="rounded bg-netral-100 dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-700 dark:text-netral-300">Umum</span>
                                    @else
                                        <span class="rounded bg-jingga-50 dark:bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-700 dark:text-jingga-300">{{ $buku->prodi->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-400">{{ $buku->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-400">{{ $buku->labelAkses() }}</td>
                                <td class="px-4 py-3">
                                    @if ($buku->is_published)
                                        <span class="rounded bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">Terbit</span>
                                    @else
                                        <span class="rounded bg-netral-100 dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-600 dark:text-netral-400">Draf</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @canany(['update', 'delete', 'terbitkan'], $buku)
                                        <div class="flex items-center justify-end gap-2">
                                            @can('terbitkan', $buku)
                                                {{-- Satu klik, langsung bisa dibalik — aksi ringan
                                                     tidak perlu gerbang modal. --}}
                                                <form method="POST" action="{{ route('admin.buku.terbit', $buku) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_published" value="{{ $buku->is_published ? 0 : 1 }}">
                                                    <button type="submit"
                                                            @if ($buku->is_published) title="Sembunyikan dari mahasiswa" @else title="Tampilkan ke mahasiswa" @endif
                                                            class="rounded border px-3 py-1.5 text-sm font-medium transition-colors @if ($buku->is_published) border-netral-200 dark:border-arang-500 text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40 @else border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 @endif">
                                                        {{ $buku->is_published ? 'Tarik' : 'Terbitkan' }}
                                                    </button>
                                                </form>
                                            @endcan

                                            @can('update', $buku)
                                                <a href="{{ route('admin.buku.edit', $buku) }}"
                                                   class="rounded border border-netral-200 dark:border-arang-500 px-3 py-1.5 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">Ubah</a>
                                            @endcan

                                            @can('delete', $buku)
                                                <x-tombol-hapus
                                                    :action="route('admin.buku.destroy', $buku)"
                                                    judul="Buang ke Tempat Sampah"
                                                    :pesan="'Buku “'.$buku->title.'” akan dipindahkan ke Tempat Sampah. Berkas PDF dan sampulnya tetap tersimpan, dan buku masih dapat dipulihkan selama masa tenggang.'" />
                                            @endcan
                                        </div>
                                    @else
                                        <p class="text-right text-xs text-netral-400">Dikelola dosen lain</p>
                                    @endcanany
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-netral-500 dark:text-netral-400">Belum ada buku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarBuku->links() }}</div>
        </div>
    </div>
</x-app-layout>