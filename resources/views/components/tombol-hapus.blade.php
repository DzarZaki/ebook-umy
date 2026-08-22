{{-- Tombol hapus dengan modal konfirmasi; form DELETE menyatu di dalamnya. --}}
@props([
    'action',
    'judul' => 'Konfirmasi Penghapusan',
    'pesan' => 'Data yang dihapus tidak dapat dikembalikan.',
    'label' => 'Hapus',
])

<div x-data="{ terbuka: false }" class="inline-block">
    <button type="button" @click="terbuka = true"
            class="rounded-sm border border-red-200 px-3 py-1.5 text-sm font-medium text-red-700 transition hover:bg-red-50">
        {{ $label }}
    </button>

    <div x-show="terbuka" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-arang-900/60 backdrop-blur-sm p-4"
         @keydown.escape.window="terbuka = false">
        <div class="w-full max-w-md rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 shadow-xl transition-colors" @click.outside="terbuka = false">
            <h2 class="text-base font-semibold text-netral-900 dark:text-netral-50">{{ $judul }}</h2>
            <p class="mt-2 text-sm text-netral-600 dark:text-netral-300">{{ $pesan }}</p>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="terbuka = false"
                        class="rounded border border-netral-200 dark:border-arang-600 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700 transition-colors">
                    Batal
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded bg-red-600 dark:bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 dark:hover:bg-red-800 shadow-sm transition-colors">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>