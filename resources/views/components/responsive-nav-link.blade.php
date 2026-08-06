@props(['active' => false])

@php
    $classes = $active
        ? 'block w-full border-s-4 border-jingga-600 bg-jingga-50 py-2 pe-4 ps-3 text-start text-base font-semibold text-jingga-900'
        : 'block w-full border-s-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-kabut-600 transition-colors hover:border-kabut-300 hover:bg-kabut-100';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>