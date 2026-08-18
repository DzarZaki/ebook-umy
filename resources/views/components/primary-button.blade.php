<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded bg-sienna px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-sienna-dark focus:outline-none focus:ring-2 focus:ring-sienna focus:ring-offset-2 focus:ring-offset-arang-deepest active:bg-sienna-dark disabled:opacity-50']) }}>
    {{ $slot }}
</button>