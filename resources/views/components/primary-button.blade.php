<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-sm bg-jingga-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-jingga-500 focus:outline-none focus:ring-2 focus:ring-jingga-500 focus:ring-offset-2 focus:ring-offset-sepia-900 active:bg-jingga-700 disabled:opacity-50']) }}>
    {{ $slot }}
</button>