<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">Tempat Sampah</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            @if (session('error'))
                <div class="mb-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="max-w-2xl text-sm text-kabut-600">
                    Buku yang dihapus disimpan di sini selama <strong>{{ $tenggangHari }} hari</strong>.
                    Berkas PDF dan sampulnya masih utuh, jadi buku dapat dikembalikan kapan saja
                    sebelum tenggatnya lewat.
                </p>

                <a href="{{ route('admin.buku.index') }}"
                   class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-semibold text-kabut-700 hover:bg-kabut-100">
                    Kembali ke Koleksi
                </a>
            </div>

            <div class="overflow-hidden border border-kabut-200 bg-white">
                <table class="min-w-full divide-y divide-kabut-200 text-sm">
                    <thead class="bg-kabut-100 text-left text-kabut-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium">Lingkup</th>
                            <th class="px-4 py-3 font-medium">Dibuang</th>
                            <th class="px-4 py-3 font-medium">Dilenyapkan</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-kabut-100">
                        @forelse ($daftarBuku as $buku)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-kabut-900">{{ $buku->title }}</p>
                                    <p class="text-xs text-kabut-500">
                                        {{ $buku->author ?? 'Tanpa penulis' }} &middot; {{ $buku->ukuranMb() }} MB
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->isUmum())
                                        <span class="rounded-sm bg-sepia-100 px-2 py-1 text-xs font-medium text-sepia-800">Umum</span>
                                    @else
                                        <span class="rounded-sm bg-jingga-50 px-2 py-1 text-xs font-medium text-jingga-800">{{ $buku->prodi?->name ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-kabut-600">
                                    {{ $buku->deleted_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->sisaHari === null)
                                        <span class="text-kabut-400">—</span>
                                    @elseif ($buku->sisaHari <= 3)
                                        <span class="rounded-sm bg-red-50 px-2 py-1 text-xs font-medium text-red-800">
                                            {{ $buku->sisaHari === 0 ? 'Hari ini' : $buku->sisaHari.' hari lagi' }}
                                        </span>
                                        <p class="mt-1 text-xs text-kabut-500">{{ $buku->dilenyapkanPada->format('d/m/Y') }}</p>
                                    @else
                                        <span class="text-kabut-600">{{ $buku->sisaHari }} hari lagi</span>
                                        <p class="mt-1 text-xs text-kabut-500">{{ $buku->dilenyapkanPada->format('d/m/Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @canany(['restore', 'forceDelete'], $buku)
                                        <div class="flex items-center justify-end gap-2">
                                            @can('restore', $buku)
                                                <div x-data="{ terbuka: false }" class="inline-block">
                                                    <button type="button" @click="terbuka = true"
                                                            class="rounded-sm bg-jingga-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-jingga-700">
                                                        Pulihkan
                                                    </button>

                                                    <div x-show="terbuka" x-cloak
                                                         class="fixed inset-0 z-50 flex items-center justify-center bg-kabut-900/60 p-4"
                                                         @keydown.escape.window="terbuka = false">
                                                        <div class="w-full max-w-md rounded-sm bg-white p-6 shadow-xl" @click.outside="terbuka = false">
                                                            <h2 class="text-base font-semibold text-kabut-900">Pulihkan Buku</h2>
                                                            <p class="mt-2 text-sm text-kabut-600">
                                                                Kembalikan buku <strong>{{ $buku->title }}</strong> ke koleksi aktif?
                                                            </p>

                                                            <div class="mt-6 flex justify-end gap-3">
                                                                <button type="button" @click="terbuka = false"
                                                                        class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                                                                    Batal
                                                                </button>
                                                                <form method="POST" action="{{ route('admin.buku-sampah.pulihkan', $buku) }}" class="inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit"
                                                                            class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                                                                        Ya, Pulihkan
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endcan

                                            @can('forceDelete', $buku)
                                                <div x-data="{ terbuka: false }" class="inline-block">
                                                    <button type="button" @click="terbuka = true"
                                                            class="rounded-sm border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">
                                                        Lenyapkan
                                                    </button>

                                                    <div x-show="terbuka" x-cloak
                                                         class="fixed inset-0 z-50 flex items-center justify-center bg-kabut-900/60 p-4"
                                                         @keydown.escape.window="terbuka = false">
                                                        <div class="w-full max-w-md rounded-sm bg-white p-6 shadow-xl" @click.outside="terbuka = false">
                                                            <h2 class="text-base font-semibold text-red-900">Lenyapkan Permanen</h2>
                                                            <p class="mt-2 text-sm text-kabut-600">
                                                                Lenyapkan <strong>{{ $buku->title }}</strong> secara permanen?
                                                                Berkas PDF dan sampulnya akan ikut terhapus, dan tidak ada cara mengembalikannya.
                                                            </p>

                                                            <div class="mt-6 flex justify-end gap-3">
                                                                <button type="button" @click="terbuka = false"
                                                                        class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                                                                    Batal
                                                                </button>
                                                                <form method="POST" action="{{ route('admin.buku-sampah.hapus', $buku) }}" class="inline">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                            class="rounded-sm bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                                                        Ya, Lenyapkan
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endcan
                                        </div>
                                    @else
                                        <p class="text-right text-xs text-kabut-400">Dikelola dosen lain</p>
                                    @endcanany
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-kabut-500">
                                    Tempat sampah kosong.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarBuku->links() }}</div>
        </div>
    </div>
</x-app-layout>