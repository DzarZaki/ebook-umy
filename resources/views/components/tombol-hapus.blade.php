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
         class="fixed inset-0 z-50 flex items-center justify-center bg-kabut-900/60 p-4"
         @keydown.escape.window="terbuka = false">
        <div class="w-full max-w-md rounded-sm bg-white p-6 shadow-xl" @click.outside="terbuka = false">
            <h2 class="text-base font-semibold text-kabut-900">{{ $judul }}</h2>
            <p class="mt-2 text-sm text-kabut-600">{{ $pesan }}</p>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="terbuka = false"
                        class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                    Batal
                </button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-sm bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">
                        Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>