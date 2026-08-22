@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold tracking-wide text-netral-700 dark:text-netral-200']) }}>
    {{ $value ?? $slot }}
</label>