@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold tracking-wide text-kabut-700']) }}>
    {{ $value ?? $slot }}
</label>