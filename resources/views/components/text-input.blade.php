@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'w-full rounded-sm border-sepia-700 bg-sepia-800 text-kabut-100 placeholder-kabut-500 shadow-none transition focus:border-jingga-500 focus:ring-1 focus:ring-jingga-500 disabled:bg-sepia-900',
    ]) !!}>