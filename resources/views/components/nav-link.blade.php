@props(['active'])

@php
$classes = ($active ?? false)
	? 'inline-flex items-center px-1 pt-1 border-b-2 border-jingga-500 text-sm font-medium leading-5 text-kabut-50 whitespace-nowrap focus:outline-none transition duration-150 ease-in-out'
	: 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-kabut-400 whitespace-nowrap hover:text-kabut-50 hover:border-sepia-600 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
	{{ $slot }}
</a>