<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded border border-arang-base bg-arang-deep px-5 py-2.5 text-sm font-semibold text-netral-200 transition-colors hover:bg-arang-base focus:outline-none focus:ring-2 focus:ring-sienna focus:ring-offset-2']) }}>
    {{ $slot }}
</button>