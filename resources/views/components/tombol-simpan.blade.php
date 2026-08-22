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
            class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white dark:bg-arang-800 ring-1 ring-netral-200 dark:ring-arang-600 shadow-sm transition-colors duration-150 hover:ring-netral-400 dark:hover:ring-arang-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600 focus-visible:ring-offset-2 motion-reduce:transition-none sm:h-9 sm:w-9 {{ $sudah ? 'text-jingga-600 dark:text-jingga-400 hover:text-jingga-700' : 'text-netral-600 dark:text-netral-300 hover:text-netral-900 dark:hover:text-netral-100' }}"
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
                ? 'border-jingga-200 dark:border-jingga-700/70 bg-jingga-50 dark:bg-arang-800 text-jingga-800 dark:text-jingga-400 hover:border-jingga-300 dark:hover:border-jingga-600'
                : 'border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-700 dark:text-netral-200 hover:bg-netral-50 dark:hover:bg-arang-600' }}"
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