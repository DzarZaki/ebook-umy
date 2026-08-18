@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'w-full rounded border-arang-base bg-arang-deep text-netral-100 placeholder-netral-400 shadow-none transition focus:border-sienna focus:ring-1 focus:ring-sienna disabled:bg-arang-deepest disabled:text-netral-400',
    ]) !!}>