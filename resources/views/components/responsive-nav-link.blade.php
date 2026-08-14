@props(['active' => false])

@php
    $classes = $active
        ? 'block w-full border-s-4 border-jingga-500 bg-sepia-800 py-2 pe-4 ps-3 text-start text-base font-semibold text-jingga-400'
        : 'block w-full border-s-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-kabut-400 transition-colors hover:border-sepia-600 hover:bg-sepia-800';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>