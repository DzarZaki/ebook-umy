@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'w-full rounded-sm border-kabut-300 bg-white text-kabut-900 placeholder-kabut-400 shadow-none transition focus:border-jingga-500 focus:ring-1 focus:ring-jingga-500 disabled:bg-kabut-100',
    ]) !!}>