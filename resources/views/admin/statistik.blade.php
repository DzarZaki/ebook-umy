<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">
            Statistik &middot; {{ auth()->user()->prodi?->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Angka ringkas --}}
            <div class="grid gap-px border border-netral-200 dark:border-arang-600 bg-netral-200 dark:bg-arang-600 sm:grid-cols-2 lg:grid-cols-4 rounded-lg overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="bg-white dark:bg-arang-700/80 p-5">
                    <p class="text-sm text-netral-500 dark:text-netral-400">Total unduhan</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-netral-900 dark:text-netral-50">{{ $totalUnduhan }}</p>
                </div>
                <div class="bg-white dark:bg-arang-700/80 p-5">
                    <p class="text-sm text-netral-500 dark:text-netral-400">30 hari terakhir</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-jingga-600 dark:text-jingga-400">{{ $unduhanSebulan }}</p>
                </div>
                <div class="bg-white dark:bg-arang-700/80 p-5">
                    <p class="text-sm text-netral-500 dark:text-netral-400">Mahasiswa pengunduh</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-netral-900 dark:text-netral-50">{{ $pengunduhUnik }}</p>
                </div>
                <div class="bg-white dark:bg-arang-700/80 p-5">
                    <p class="text-sm text-netral-500 dark:text-netral-400">Mahasiswa terdaftar</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-netral-900 dark:text-netral-50">{{ $jumlahMahasiswa }}</p>
                </div>
            </div>

            {{-- Grafik batang sederhana --}}
            @php($puncak = max(1, collect($grafikHarian)->max('jumlah')))

            <div class="mt-8 border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/60 p-6 rounded-lg shadow-sm dark:shadow-none transition-colors">
                <h3 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Unduhan 14 hari terakhir</h3>

                <div class="mt-6 flex h-40 items-end gap-1.5">
                    @foreach ($grafikHarian as $hari)
                        <div class="group flex flex-1 flex-col items-center justify-end gap-1.5">
                            <span class="text-[10px] font-medium text-netral-500 dark:text-netral-400">{{ $hari['jumlah'] ?: '' }}</span>
                            <div class="w-full bg-jingga-500 dark:bg-jingga-400 transition-colors group-hover:bg-jingga-700 dark:group-hover:bg-jingga-300 rounded-t-sm"
                                 style="height: {{ max(2, round($hari['jumlah'] / $puncak * 100)) }}%"
                                 title="{{ $hari['label'] }}: {{ $hari['jumlah'] }} unduhan"></div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 flex gap-1.5 border-t border-netral-200 dark:border-arang-600 pt-2">
                    @foreach ($grafikHarian as $indeks => $hari)
                        <span class="flex-1 text-center text-[10px] text-netral-500 dark:text-netral-400">
                            {{ $indeks % 2 === 0 ? $hari['label'] : '' }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">

                {{-- Buku terpopuler --}}
                <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/60 rounded-lg overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <h3 class="border-b border-netral-200 dark:border-arang-600 px-5 py-4 font-display text-lg font-semibold text-netral-900 dark:text-netral-50">
                        Buku paling banyak diunduh
                    </h3>

                    <ol class="divide-y divide-netral-200 dark:divide-arang-600">
                        @forelse ($bukuTerpopuler as $urutan => $buku)
                            <li class="flex items-center gap-4 px-5 py-3">
                                <span class="w-6 shrink-0 font-display text-lg text-netral-400">{{ $urutan + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-netral-900 dark:text-netral-50">{{ $buku->title }}</p>
                                    <p class="text-xs text-netral-500 dark:text-netral-400">{{ $buku->prodi?->name ?? 'Umum' }}</p>
                                </div>
                                <span class="shrink-0 rounded bg-jingga-50 dark:bg-jingga-900/30 px-2 py-1 text-xs font-semibold text-jingga-700 dark:text-jingga-300">
                                    {{ $buku->download_logs_count }}&times;
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-netral-500 dark:text-netral-400">Belum ada unduhan tercatat.</li>
                        @endforelse
                    </ol>
                </div>

                {{-- Catatan aktivitas --}}
                <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/60 rounded-lg overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <h3 class="border-b border-netral-200 dark:border-arang-600 px-5 py-4 font-display text-lg font-semibold text-netral-900 dark:text-netral-50">
                        Aktivitas terbaru
                    </h3>

                    <ul class="divide-y divide-netral-200 dark:divide-arang-600">
                        @forelse ($catatanTerbaru as $catatan)
                            <li class="px-5 py-3">
                                <p class="text-sm text-netral-900 dark:text-netral-100">
                                    <strong class="font-semibold">{{ $catatan->user?->name ?? 'Pengguna dihapus' }}</strong>
                                    mengunduh
                                    <span class="text-netral-700 dark:text-netral-300">{{ $catatan->book?->title ?? 'Buku dihapus' }}</span>
                                </p>
                                <p class="text-xs text-netral-500 dark:text-netral-400">
                                    {{ $catatan->user?->email }} &middot; {{ $catatan->created_at->diffForHumans() }}
                                </p>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-netral-500">Belum ada aktivitas.</li>
                        @endforelse
                    </ul>

                    @if ($catatanTerbaru->hasPages())
                        <div class="border-t border-netral-200 px-5 py-3">{{ $catatanTerbaru->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>