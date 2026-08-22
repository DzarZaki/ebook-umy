@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge([
        'class' => 'w-full rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-netral-900 dark:text-netral-100 placeholder-netral-500 dark:placeholder-netral-400 shadow-none transition focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-1 focus:ring-jingga-500 disabled:bg-netral-100 dark:disabled:bg-arang-900 disabled:text-netral-400',
    ]) !!}>