@props([
    'buku',
    'tersimpan' => null,
    'gaya' => 'penuh',
])

@php
    // Status boleh dipaksa lewat prop :tersimpan (dipakai halaman Koleksi
    // Saya, yang sudah tahu semuanya tersimpan). Bila tidak, dibaca dari
    // buku — dan itu hanya bermakna bila kuerinya memakai scope
    // denganStatusSimpan(). Lihat Book::sudahDisimpan().
    $sudah = $tersimpan ?? $buku->sudahDisimpan();

    $label = $sudah
        ? 'Keluarkan dari Koleksi Saya'
        : 'Simpan ke Koleksi Saya';
@endphp

<form
    method="POST"
    action="{{ $sudah ? route('koleksi.lepas', $buku) : route('koleksi.simpan', $buku) }}"
    {{ $attributes->merge(['class' => 'inline-block']) }}
>
    @csrf

    @if ($sudah)
        @method('DELETE')
    @endif

    @if ($gaya === 'ikon')
        {{-- Untuk sudut kartu buku: hanya ikon, tanpa teks.

             Ukurannya 40 px di layar sentuh lalu menyusut menjadi 36 px di
             layar lebar. Sasaran sentuh di bawah 40 px sulit ditekan dengan
             jempol, sedangkan dengan tetikus 36 px sudah lebih dari cukup —
             dan di sudut sampul, setiap piksel yang tidak perlu menutupi
             gambarnya adalah piksel yang dirampas dari buku itu sendiri. --}}
        <button
            type="submit"
            title="{{ $label }}"
            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white ring-1 ring-kabut-200 transition-colors duration-150 hover:ring-kabut-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none sm:h-9 sm:w-9 {{ $sudah ? 'text-jingga-600 hover:text-jingga-700' : 'text-kabut-400 hover:text-kabut-700' }}"
        >
            <svg class="h-5 w-5" viewBox="0 0 20 20" aria-hidden="true"
                 fill="{{ $sudah ? 'currentColor' : 'none' }}"
                 stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M5.5 3.5h9a1 1 0 0 1 1 1v12l-5.5-3-5.5 3v-12a1 1 0 0 1 1-1Z" />
            </svg>
            <span class="sr-only">{{ $label }}</span>
        </button>
    @else
        {{-- Untuk halaman detail dan daftar: ikon beserta teksnya. --}}
        <button
            type="submit"
            title="{{ $label }}"
            class="inline-flex cursor-pointer items-center gap-2 rounded border px-3 py-2 text-sm font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none {{ $sudah
                ? 'border-jingga-200 bg-jingga-50 text-jingga-800 hover:border-jingga-300 hover:bg-jingga-100'
                : 'border-kabut-300 bg-white text-kabut-700 hover:border-kabut-400 hover:bg-kabut-50' }}"
        >
            <svg class="h-4 w-4" viewBox="0 0 20 20" aria-hidden="true"
                 fill="{{ $sudah ? 'currentColor' : 'none' }}"
                 stroke="currentColor" stroke-width="1.6">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M5.5 3.5h9a1 1 0 0 1 1 1v12l-5.5-3-5.5 3v-12a1 1 0 0 1 1-1Z" />
            </svg>
            {{ $sudah ? 'Tersimpan' : 'Simpan' }}
        </button>
    @endif
</form>