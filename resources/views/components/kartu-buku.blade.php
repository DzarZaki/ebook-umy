@props([
    // Buku yang digambar. Wajib.
    'buku',

    // Status pita simpan. Biarkan null agar diambil dari scope
    // denganStatusSimpan(); isi eksplisit bila konteksnya sudah pasti
    // (misalnya daftar buku tersimpan, yang semuanya tersimpan).
    'tersimpan' => null,

    // Kemajuan membaca: ['halaman' => int, 'total' => int|null, 'persen' => int].
    // Bila diisi, kartu menampilkan bilah kemajuan dan tombol Lanjutkan.
    'kemajuan' => null,

    // Lencana kecil di sudut kiri atas sampul, misalnya "Baru".
    'lencana' => null,

    // Tampilkan lencana prodi/kategori di badan kartu.
    'label' => false,

    // Sembunyikan pita simpan bila kartu dipakai di tempat yang
    // penyimpanannya tidak relevan.
    'pitaSimpan' => true,

    // Tujuan klik. Bawaannya halaman detail buku.
    'tautan' => null,
])

@php
    $alamat = $tautan ?? route('katalog.show', $buku);
    $sudahDisimpan = $tersimpan ?? $buku->sudahDisimpan();

    $halaman = $kemajuan['halaman'] ?? null;
    $totalHalaman = $kemajuan['total'] ?? null;
    $persen = $kemajuan['persen'] ?? null;

    // Buku umum tidak punya prodi. Tanda tanya pada prodi?->name adalah
    // jaring kedua: bila is_umum salah menilai tetapi prodi_id kosong,
    // kartu tetap tergambar alih-alih melempar galat.
    $namaProdi = $buku->isUmum() ? 'Umum' : ($buku->prodi?->name ?? 'Umum');
@endphp

{{--
    Kartu buku bergaya kertas.

    Tiga keputusan yang membedakannya dari blok lama:

    1. TANPA BAYANGAN. Pada gaya kertas, kartu dipisahkan garis rambut
       (border-netral-200), bukan bayangan. Kertas tidak melayang.
    2. h-full DAN flex-1. Kartu mengisi penuh tinggi selnya di grid, dan
       badan kartu memuai; akibatnya semua kartu dalam satu baris berujung
       rata meskipun panjang judulnya berbeda.
    3. PITA DITEMPATKAN OLEH KARTU, bukan oleh tombolnya sendiri. Pembungkus
       absolute di bawah ini yang menentukan posisi, sehingga tombol simpan
       tidak perlu tahu apa pun soal tata letak — itulah sebabnya pita kemarin
       jatuh ke barisnya sendiri.
--}}
<div {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 transition duration-200 hover:border-netral-300 dark:hover:border-arang-500 shadow-sm dark:shadow-none motion-reduce:transition-none']) }}>

    {{-- ===== Sampul ===== --}}
    {{--
        Tautan sampul disembunyikan dari pembaca layar dan dari urutan Tab
        (aria-hidden + tabindex="-1") karena tautan judul di bawahnya menuju
        tempat yang sama. Tanpa itu, pengguna keyboard menekan Tab dua kali
        untuk satu buku.
    --}}
    <a href="{{ $alamat }}"
       aria-hidden="true"
       tabindex="-1"
       class="block aspect-[3/4] w-full overflow-hidden bg-netral-100 dark:bg-arang-800">
        @if ($buku->coverUrl())
            {{-- object-cover, bukan object-contain: sampul memenuhi kotaknya
                 tanpa bingkai abu-abu di kiri-kanan. Sedikit terpotong lebih
                 baik daripada melayang di tengah ruang kosong. --}}
            <img src="{{ $buku->coverUrl() }}"
                 alt=""
                 loading="lazy"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none">
        @else
            {{-- Buku tanpa sampul: kotak inisial setinggi penuh, memusat,
                 sehingga tingginya sama dengan kartu bersampul. --}}
            <span class="flex h-full w-full items-center justify-center bg-netral-100 dark:bg-arang-800 font-display text-4xl font-semibold text-netral-700 dark:text-netral-200">
                {{ $buku->inisial() }}
            </span>
        @endif
    </a>

    {{-- Lencana di kiri atas, misalnya "Baru". --}}
    @if ($lencana)
        <span class="absolute left-2 top-2 z-10 rounded border border-jingga-200 dark:border-jingga-700/60 bg-jingga-50/90 dark:bg-arang-800/90 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-jingga-800 dark:text-jingga-400">
            {{ $lencana }}
        </span>
    @endif

    {{-- Pita simpan melayang di kanan atas. Pembungkus inilah yang
         memposisikannya; tombolnya sendiri tidak diberi kelas posisi. --}}
    @if ($pitaSimpan)
        <div class="absolute right-2 top-2 z-10">
            <x-tombol-simpan :buku="$buku" :tersimpan="$sudahDisimpan" gaya="ikon" />
        </div>
    @endif

    {{-- ===== Badan kartu ===== --}}
    <div class="flex flex-1 flex-col gap-1 border-t border-netral-200 dark:border-arang-600 p-3">

        {{-- Lencana prodi & kategori. --}}
        @if ($label)
            <div class="mb-1 flex flex-wrap gap-1.5">
                <span class="rounded border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] text-netral-600 dark:text-netral-300">
                    {{ $namaProdi }}
                </span>

                @if ($buku->category)
                    <span class="rounded border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-800 px-1.5 py-0.5 text-[11px] text-netral-700 dark:text-netral-200">
                        {{ $buku->category->name }}
                    </span>
                @endif
            </div>
        @endif

        <h3 class="font-display text-sm font-semibold leading-snug text-netral-900 dark:text-netral-100">
            <a href="{{ $alamat }}"
               class="cursor-pointer transition-colors duration-150 hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                {{ $buku->title }}
            </a>
        </h3>

        <p class="text-xs text-netral-500 dark:text-netral-400">{{ $buku->author ?: 'Tanpa penulis' }}</p>

        {{-- mt-auto mendorong keterangan ini ke dasar kartu, sehingga
             seluruh kartu dalam satu baris punya garis dasar yang sama. --}}
        <p class="mt-auto pt-2 text-[11px] text-netral-500 dark:text-netral-400">
            {{ $buku->labelAkses() }}
        </p>

        {{-- ===== Kemajuan membaca (opsional) ===== --}}
        @if ($persen !== null)
            <div class="pt-2">
                {{-- Bilah selalu tampak minimal 2% agar kemajuan yang baru
                     sedikit tetap terlihat sebagai garis, bukan hilang. --}}
                <div class="h-1 w-full overflow-hidden rounded-full bg-netral-200 dark:bg-arang-600"
                     role="progressbar"
                     aria-valuenow="{{ $persen }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="Kemajuan membaca {{ $buku->title }}">
                    <div class="h-full rounded-full bg-jingga-600 dark:bg-jingga-500" style="width: {{ max(2, $persen) }}%"></div>
                </div>

                <p class="mt-1.5 text-[11px] text-netral-500 dark:text-netral-400">
                    Halaman {{ $halaman }}@if ($totalHalaman) dari {{ $totalHalaman }}@endif
                    @if ($persen >= 1)
                        · {{ $persen }}%
                    @else
                        · baru dimulai
                    @endif
                </p>

                <a href="{{ route('katalog.baca', ['buku' => $buku, 'halaman' => $halaman]) }}"
                   class="mt-2 inline-flex cursor-pointer items-center rounded bg-jingga-600 dark:bg-jingga-500 px-2.5 py-1 text-xs font-semibold text-white transition-colors duration-150 hover:bg-jingga-700 dark:hover:bg-jingga-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none">
                    Lanjutkan
                </a>
            </div>
        @endif

        {{-- Ruang bebas untuk pemanggil: keping penanda halaman, tombol
             tambahan, atau keterangan khusus halaman tertentu. --}}
        @if ($slot->isNotEmpty())
            <div class="pt-2">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>