@props(['active'])

@php
// Tautan aktif diberi garis bawah jingga; whitespace-nowrap mencegah teks pecah dua baris.
$classes = ($active ?? false)
	? 'inline-flex items-center px-1 pt-1 border-b-2 border-jingga-600 text-sm font-medium leading-5 text-sepia-800 whitespace-nowrap focus:outline-none transition duration-150 ease-in-out'
	: 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-kabut-600 whitespace-nowrap hover:text-sepia-800 hover:border-kabut-200 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
	{{ $slot }}
</a>