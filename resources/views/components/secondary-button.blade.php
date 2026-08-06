<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-sm border border-kabut-300 bg-white px-5 py-2.5 text-sm font-semibold text-kabut-700 transition-colors hover:bg-kabut-100 focus:outline-none focus:ring-2 focus:ring-kabut-400 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>