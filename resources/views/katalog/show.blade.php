<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('katalog.index') }}" class="text-sm font-medium text-kabut-600 underline underline-offset-4 hover:text-kabut-900">
            &larr; Kembali ke katalog
        </a>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            <div class="grid gap-8 border border-kabut-200 bg-white p-6 sm:p-8 lg:grid-cols-12">

                {{-- Sampul --}}
                <div class="lg:col-span-4">
                    @if ($buku->coverUrl())
                        <img src="{{ $buku->coverUrl() }}" alt="Sampul {{ $buku->title }}"
                             class="w-full border border-kabut-200 object-cover">
                    @else
                        <span class="flex aspect-[3/4] w-full items-center justify-center border border-sepia-800 bg-sepia-800 font-display text-5xl font-semibold text-kabut-50">
                            {{ $buku->inisial() }}
                        </span>
                    @endif
                </div>

                {{-- Keterangan --}}
                <div class="lg:col-span-8">
                    <div class="flex flex-wrap gap-2">
                        @if ($buku->isUmum())
                            <span class="rounded-sm bg-sepia-100 px-2 py-1 text-xs font-medium text-sepia-800">Umum</span>
                        @else
                            <span class="rounded-sm bg-jingga-50 px-2 py-1 text-xs font-medium text-jingga-800">{{ $buku->prodi->name }}</span>
                        @endif

                        @if ($buku->category)
                            <span class="rounded-sm bg-kabut-100 px-2 py-1 text-xs font-medium text-kabut-600">{{ $buku->category->name }}</span>
                        @endif

                        @unless ($buku->is_published)
                            <span class="rounded-sm bg-red-50 px-2 py-1 text-xs font-medium text-red-800">Draf</span>
                        @endunless
                    </div>

                    <h1 class="mt-3 font-display text-3xl font-semibold leading-tight text-kabut-900">{{ $buku->title }}</h1>
                    <p class="mt-2 text-sm text-kabut-500">{{ $buku->author ?? 'Tanpa penulis' }}</p>

                    @if ($buku->description)
                        <p class="mt-5 text-sm leading-relaxed text-kabut-700">{{ $buku->description }}</p>
                    @endif

                    <dl class="mt-6 grid gap-px border border-kabut-200 bg-kabut-200 text-sm sm:grid-cols-2">
                        <div class="bg-white px-4 py-3">
                            <dt class="text-xs text-kabut-500">Aturan unduh</dt>
                            <dd class="mt-0.5 font-medium text-kabut-900">{{ $buku->labelAkses() }}</dd>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <dt class="text-xs text-kabut-500">Ukuran berkas</dt>
                            <dd class="mt-0.5 font-medium text-kabut-900">{{ $buku->ukuranMb() }} MB</dd>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <dt class="text-xs text-kabut-500">Jumlah halaman</dt>
                            <dd class="mt-0.5 font-medium text-kabut-900">{{ $buku->page_count ?? 'Tidak dicantumkan' }}</dd>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <dt class="text-xs text-kabut-500">Diunggah oleh</dt>
                            <dd class="mt-0.5 font-medium text-kabut-900">{{ $buku->pengunggah?->name ?? '—' }}</dd>
                        </div>
                    </dl>

                    @php($aturan = $buku->aturanUnduhUntuk(auth()->user()))

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ route('katalog.baca', $buku) }}"
                        class="rounded-sm bg-jingga-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-jingga-700">
                            Baca Buku
                        </a>
                        <span class="text-xs text-kabut-500">{{ $aturan['alasan'] }}</span>
                    </div>

                    @if ($buku->watermark_enabled)
                        <p class="mt-4 border border-kabut-200 bg-kabut-50 p-3 text-xs text-kabut-600">
                            Berkas yang Anda unduh akan mencantumkan nama dan email Anda sebagai penanda kepemilikan.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Bacaan lain --}}
            @if ($serupa->isNotEmpty())
                <h2 class="mt-10 font-display text-lg font-semibold text-kabut-900">Bacaan lain yang mungkin cocok</h2>

                <ul class="mt-3 divide-y divide-kabut-200 border border-kabut-200 bg-white">
                    @foreach ($serupa as $lain)
                        <li class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <a href="{{ route('katalog.show', $lain) }}" class="text-sm font-semibold text-kabut-900 hover:text-jingga-700">
                                    {{ $lain->title }}
                                </a>
                                <p class="text-xs text-kabut-500">{{ $lain->author ?? 'Tanpa penulis' }} &middot; {{ $lain->labelAkses() }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-kabut-400">{{ $lain->prodi?->name ?? 'Umum' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>