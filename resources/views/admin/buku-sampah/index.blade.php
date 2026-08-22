<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Tempat Sampah</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            @if (session('error'))
                <div class="mb-4 border border-red-200 dark:border-red-700/50 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <p class="max-w-2xl text-sm text-netral-600 dark:text-netral-400">
                    Buku yang dihapus disimpan di sini selama <strong>{{ $tenggangHari }} hari</strong>.
                    Berkas PDF dan sampulnya masih utuh, jadi buku dapat dikembalikan kapan saja
                    sebelum tenggatnya lewat.
                </p>

                <a href="{{ route('admin.buku.index') }}"
                   class="rounded border border-netral-200 dark:border-arang-500 bg-white dark:bg-arang-700 px-4 py-2 text-sm font-semibold text-netral-700 dark:text-netral-300 hover:bg-netral-50 dark:hover:bg-arang-600 shadow-sm transition-colors">
                    Kembali ke Koleksi
                </a>
            </div>

            <div class="overflow-hidden border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg shadow-sm dark:shadow-none transition-colors">
                <table class="min-w-full divide-y divide-netral-200 dark:divide-arang-600 text-sm">
                    <thead class="bg-netral-50 dark:bg-arang-700 text-left text-netral-600 dark:text-netral-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium">Lingkup</th>
                            <th class="px-4 py-3 font-medium">Dibuang</th>
                            <th class="px-4 py-3 font-medium">Dilenyapkan</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-netral-200 dark:divide-arang-600">
                        @forelse ($daftarBuku as $buku)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-netral-900 dark:text-netral-50">{{ $buku->title }}</p>
                                    <p class="text-xs text-netral-500 dark:text-netral-400">
                                        {{ $buku->author ?? 'Tanpa penulis' }} &middot; {{ $buku->ukuranMb() }} MB
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->isUmum())
                                        <span class="rounded bg-netral-100 dark:bg-arang-700 px-2 py-1 text-xs font-medium text-netral-700 dark:text-netral-300">Umum</span>
                                    @else
                                        <span class="rounded bg-jingga-50 dark:bg-jingga-900/30 px-2 py-1 text-xs font-medium text-jingga-700 dark:text-jingga-300">{{ $buku->prodi?->name ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-netral-600 dark:text-netral-400">
                                    {{ $buku->deleted_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($buku->sisaHari === null)
                                        <span class="text-netral-400">—</span>
                                    @elseif ($buku->sisaHari <= 3)
                                        <span class="rounded bg-red-50 dark:bg-red-900/30 px-2 py-1 text-xs font-medium text-red-700 dark:text-red-300">
                                            {{ $buku->sisaHari === 0 ? 'Hari ini' : $buku->sisaHari.' hari lagi' }}
                                        </span>
                                        <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">{{ $buku->dilenyapkanPada->format('d/m/Y') }}</p>
                                    @else
                                        <span class="text-netral-700 dark:text-netral-300">{{ $buku->sisaHari }} hari lagi</span>
                                        <p class="mt-1 text-xs text-netral-500 dark:text-netral-400">{{ $buku->dilenyapkanPada->format('d/m/Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @canany(['restore', 'forceDelete'], $buku)
                                        <div class="flex items-center justify-end gap-2">
                                            @can('restore', $buku)
                                                <div x-data="{ terbuka: false }" class="inline-block">
                                                    <button type="button" @click="terbuka = true"
                                                            class="rounded bg-jingga-600 dark:bg-jingga-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                                                        Pulihkan
                                                    </button>

                                                    <div x-show="terbuka" x-cloak
                                                         class="fixed inset-0 z-50 flex items-center justify-center bg-arang-900/60 backdrop-blur-sm p-4"
                                                         @keydown.escape.window="terbuka = false">
                                                        <div class="w-full max-w-md rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 shadow-xl transition-colors" @click.outside="terbuka = false">
                                                            <h2 class="text-base font-semibold text-netral-900 dark:text-netral-50">Pulihkan Buku</h2>
                                                            <p class="mt-2 text-sm text-netral-600 dark:text-netral-300">
                                                                Buku &ldquo;{{ $buku->title }}&rdquo; akan dikembalikan ke koleksi
                                                                dan dapat diakses kembali oleh mahasiswa. Lanjutkan?
                                                            </p>

                                                            <div class="mt-6 flex justify-end gap-3">
                                                                <button type="button" @click="terbuka = false"
                                                                        class="rounded border border-netral-200 dark:border-arang-600 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700 transition-colors">
                                                                    Batal
                                                                </button>
                                                                <form method="POST" action="{{ route('admin.buku-sampah.pulihkan', $buku) }}">
                                                                    @csrf @method('PATCH')
                                                                    <button type="submit"
                                                                            class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
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
                                                            class="rounded border border-red-200 dark:border-red-700/50 bg-red-50 dark:bg-red-900/30 px-3 py-1.5 text-sm font-medium text-red-700 dark:text-red-300 transition hover:bg-red-100 dark:hover:bg-red-900/50">
                                                        Lenyapkan
                                                    </button>

                                                    <div x-show="terbuka" x-cloak
                                                         class="fixed inset-0 z-50 flex items-center justify-center bg-arang-900/60 backdrop-blur-sm p-4"
                                                         @keydown.escape.window="terbuka = false">
                                                        <div class="w-full max-w-md rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 shadow-xl transition-colors" @click.outside="terbuka = false">
                                                            <h2 class="text-base font-semibold text-netral-900 dark:text-netral-50">Lenyapkan Selamanya</h2>
                                                            <p class="mt-2 text-sm text-netral-600 dark:text-netral-300">
                                                                Buku &ldquo;{{ $buku->title }}&rdquo; dan seluruh berkasnya
                                                                akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                                                            </p>

                                                            <div class="mt-6 flex justify-end gap-3">
                                                                <button type="button" @click="terbuka = false"
                                                                        class="rounded border border-netral-200 dark:border-arang-600 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700 transition-colors">
                                                                    Batal
                                                                </button>
                                                                <form method="POST" action="{{ route('admin.buku-sampah.hapus', $buku) }}">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit"
                                                                            class="rounded bg-red-600 dark:bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 dark:hover:bg-red-800 shadow-sm transition-colors">
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
                                        <p class="text-right text-xs text-netral-400">Dikelola dosen lain</p>
                                    @endcanany
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-netral-500 dark:text-netral-400">
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