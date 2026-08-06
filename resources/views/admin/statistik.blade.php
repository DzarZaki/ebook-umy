<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">
            Statistik &middot; {{ auth()->user()->prodi?->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Angka ringkas --}}
            <div class="grid gap-px border border-kabut-200 bg-kabut-200 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-5">
                    <p class="text-sm text-kabut-500">Total unduhan</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-kabut-900">{{ $totalUnduhan }}</p>
                </div>
                <div class="bg-white p-5">
                    <p class="text-sm text-kabut-500">30 hari terakhir</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-jingga-700">{{ $unduhanSebulan }}</p>
                </div>
                <div class="bg-white p-5">
                    <p class="text-sm text-kabut-500">Mahasiswa pengunduh</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-kabut-900">{{ $pengunduhUnik }}</p>
                </div>
                <div class="bg-white p-5">
                    <p class="text-sm text-kabut-500">Mahasiswa terdaftar</p>
                    <p class="mt-1 font-display text-3xl font-semibold text-kabut-900">{{ $jumlahMahasiswa }}</p>
                </div>
            </div>

            {{-- Grafik batang sederhana, digambar murni dengan CSS --}}
            @php($puncak = max(1, collect($grafikHarian)->max('jumlah')))

            <div class="mt-8 border border-kabut-200 bg-white p-6">
                <h3 class="font-display text-lg font-semibold text-kabut-900">Unduhan 14 hari terakhir</h3>

                <div class="mt-6 flex h-40 items-end gap-1.5">
                    @foreach ($grafikHarian as $hari)
                        <div class="group flex flex-1 flex-col items-center justify-end gap-1.5">
                            <span class="text-[10px] font-medium text-kabut-500">{{ $hari['jumlah'] ?: '' }}</span>
                            <div class="w-full bg-jingga-500 transition-colors group-hover:bg-jingga-700"
                                 style="height: {{ max(2, round($hari['jumlah'] / $puncak * 100)) }}%"
                                 title="{{ $hari['label'] }}: {{ $hari['jumlah'] }} unduhan"></div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2 flex gap-1.5 border-t border-kabut-200 pt-2">
                    @foreach ($grafikHarian as $indeks => $hari)
                        <span class="flex-1 text-center text-[10px] text-kabut-400">
                            {{ $indeks % 2 === 0 ? $hari['label'] : '' }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">

                {{-- Buku terpopuler --}}
                <div class="border border-kabut-200 bg-white">
                    <h3 class="border-b border-kabut-200 px-5 py-4 font-display text-lg font-semibold text-kabut-900">
                        Buku paling banyak diunduh
                    </h3>

                    <ol class="divide-y divide-kabut-100">
                        @forelse ($bukuTerpopuler as $urutan => $buku)
                            <li class="flex items-center gap-4 px-5 py-3">
                                <span class="w-6 shrink-0 font-display text-lg text-kabut-400">{{ $urutan + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-kabut-900">{{ $buku->title }}</p>
                                    <p class="text-xs text-kabut-500">{{ $buku->prodi?->name ?? 'Umum' }}</p>
                                </div>
                                <span class="shrink-0 rounded-sm bg-jingga-50 px-2 py-1 text-xs font-semibold text-jingga-800">
                                    {{ $buku->download_logs_count }}&times;
                                </span>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-kabut-500">Belum ada unduhan tercatat.</li>
                        @endforelse
                    </ol>
                </div>

                {{-- Catatan aktivitas --}}
                <div class="border border-kabut-200 bg-white">
                    <h3 class="border-b border-kabut-200 px-5 py-4 font-display text-lg font-semibold text-kabut-900">
                        Aktivitas terbaru
                    </h3>

                    <ul class="divide-y divide-kabut-100">
                        @forelse ($catatanTerbaru as $catatan)
                            <li class="px-5 py-3">
                                <p class="text-sm text-kabut-900">
                                    <strong class="font-semibold">{{ $catatan->user?->name ?? 'Pengguna dihapus' }}</strong>
                                    mengunduh
                                    <span class="text-kabut-700">{{ $catatan->book?->title ?? 'Buku dihapus' }}</span>
                                </p>
                                <p class="text-xs text-kabut-500">
                                    {{ $catatan->user?->email }} &middot; {{ $catatan->created_at->diffForHumans() }}
                                </p>
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center text-sm text-kabut-500">Belum ada aktivitas.</li>
                        @endforelse
                    </ul>

                    @if ($catatanTerbaru->hasPages())
                        <div class="border-t border-kabut-200 px-5 py-3">{{ $catatanTerbaru->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>