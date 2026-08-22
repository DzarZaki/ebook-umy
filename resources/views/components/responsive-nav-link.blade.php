@props(['active' => false])

@php
    $classes = $active
        ? 'block w-full border-s-4 border-jingga-600 dark:border-jingga-400 bg-jingga-50/70 dark:bg-arang-700 py-2 pe-4 ps-3 text-start text-base font-semibold text-jingga-700 dark:text-jingga-400'
        : 'block w-full border-s-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-netral-600 dark:text-netral-400 transition-colors hover:bg-netral-100 dark:hover:bg-arang-700 hover:text-netral-900 dark:hover:text-netral-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>