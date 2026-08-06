<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-sm bg-red-800 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-700 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>