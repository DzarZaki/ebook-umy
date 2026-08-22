@props(['active'])

@php
$classes = ($active ?? false)
	? 'inline-flex items-center px-1 pt-1 border-b-2 border-jingga-600 dark:border-jingga-400 text-sm font-semibold leading-5 text-jingga-600 dark:text-netral-50 whitespace-nowrap focus:outline-none transition duration-150 ease-in-out'
	: 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-netral-500 dark:text-netral-400 whitespace-nowrap hover:text-netral-900 dark:hover:text-netral-50 hover:border-netral-300 dark:hover:border-arang-500 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
	{{ $slot }}
</a>