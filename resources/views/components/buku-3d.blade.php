@props([
    // Model buku. Wajib.
    'buku',
    // Paksa ketebalan (px). Biarkan null agar dihitung dari jumlah halaman.
    'tebalPaksa' => null,
    // Tulis judul di punggung buku bila punggungnya cukup lebar.
    'punggung' => true,
])

@php
    /**
     * Ketebalan dari jumlah halaman.
     *
     * Buku 242 halaman menjadi 30 px, buku 50 halaman menjadi 9 px.
     * Batas bawah 9 px dijaga supaya buku tipis tetap terlihat sebagai
     * benda, bukan sebagai kartu. Batas atas 44 px dijaga supaya buku
     * seribu halaman tidak berubah menjadi balok.
     */
    $tebal = $tebalPaksa ?? max(9, min(44, (int) round(($buku->page_count ?: 60) / 8)));

    /**
     * Warna punggung yang tetap untuk setiap buku.
     *
     * Diambil dari slug lewat crc32, jadi satu buku selalu mendapat warna
     * yang sama di setiap halaman dan setiap kunjungan — tetapi rak berisi
     * punggung berwarna-warni seperti rak sungguhan. Ini penawar langsung
     * untuk delapan belas buku contoh yang bersampul kembar.
     */
    $paletPunggung = [
        '#1A1D26', // arang-700
        '#A35232', // jingga-700
        '#12151C', // arang-800
        '#2A2E3A', // arang-600
        '#8B4429', // jingga-800
        '#3D3A37', // netral-700
        '#6F3520', // jingga-900
        '#2A2825', // netral-800
    ];

    $warnaPunggung = $paletPunggung[
        abs(crc32((string) ($buku->slug ?: $buku->title))) % count($paletPunggung)
    ];

    // Judul di punggung hanya masuk akal bila ada ruangnya.
    $punggungBertulisan = $punggung && $tebal >= 20;
@endphp

{{--
    Kelas `group` dipasang di sini supaya buku berputar saat kursor
    menyentuhnya. Bila komponen ini diletakkan di dalam kartu yang juga
    ber-`group`, keduanya tetap bekerja: aturan CSS-nya berbasis keturunan.

    `data-miring` dibaca oleh animasi.js untuk mengikuti kursor. Pada
    perangkat sentuh dan pada mode "kurangi gerak", atribut ini diabaikan
    dan buku berdiri diam pada sudut bawaannya.
--}}
<div {{ $attributes->merge(['class' => 'rak-3d group relative block']) }} data-miring>

    <div class="buku3d"
         style="--tebal: {{ $tebal }}px; --warna-punggung: {{ $warnaPunggung }};">

        {{-- Punggung: berdiri di tepi kiri, menghilang ke belakang --}}
        <span class="buku3d__punggung" aria-hidden="true">
            @if ($punggungBertulisan)
                <span class="flex h-full w-full select-none items-center justify-center overflow-hidden py-4 text-[8px] font-semibold uppercase tracking-[0.18em] text-white/70"
                      style="writing-mode: vertical-rl;">
                    {{ \Illuminate\Support\Str::limit($buku->title, 38) }}
                </span>
            @endif
        </span>

        {{-- Tumpukan kertas di sisi kanan --}}
        <span class="buku3d__kertas" aria-hidden="true"></span>

        {{-- Sampul depan --}}
        <div class="buku3d__muka aspect-[3/4] w-full">
            @if ($buku->coverUrl())
                <img src="{{ $buku->coverUrl() }}"
                     alt=""
                     loading="lazy"
                     decoding="async"
                     class="h-full w-full object-cover">
            @else
                {{--
                    Sampul tiruan untuk buku tanpa gambar. Bukan kotak kosong
                    berinisial, melainkan sampul yang dirancang: inisial besar,
                    judul kecil di bawahnya, dan satu garis pemisah. Warnanya
                    mengikuti punggungnya sehingga buku terasa satu benda.
                --}}
                <div class="flex h-full w-full flex-col justify-between p-3 text-center"
                     style="background-color: {{ $warnaPunggung }};">
                    <span class="text-[7px] font-semibold uppercase tracking-[0.2em] text-white/50">
                        Pustaka Dosen
                    </span>

                    <span class="select-none font-display text-4xl font-semibold leading-none text-white/90">
                        {{ $buku->inisial() }}
                    </span>

                    <span class="mx-auto w-full">
                        <span class="mb-1.5 block h-px w-8 bg-white/30" aria-hidden="true"></span>
                        <span class="line-clamp-2 block text-[8px] font-medium uppercase tracking-wider text-white/60">
                            {{ $buku->title }}
                        </span>
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Bayangan di meja. Di luar .buku3d agar tidak ikut berputar. --}}
    <span class="buku3d__bayang" aria-hidden="true"></span>
</div>