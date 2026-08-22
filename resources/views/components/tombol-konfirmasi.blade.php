{{--
    Tombol simpan yang memunculkan modal konfirmasi terlebih dahulu.
    Form divalidasi browser dulu, baru modal muncul.
--}}
@props([
    'form-id',
    'judul' => 'Konfirmasi Penyimpanan',
    'pesan' => 'Pastikan data yang Anda masukkan sudah benar sebelum disimpan.',
    'label' => 'Simpan',
    'labelKonfirmasi' => 'Ya, Simpan',
])

<div x-data="{ terbuka: false }" class="inline-block">
    <button
        type="button"
        @click="document.getElementById('{{ $formId }}').reportValidity() && (terbuka = true)"
        class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-jingga-700 focus:outline-none focus:ring-2 focus:ring-jingga-500 focus:ring-offset-2"
    >
        {{ $label }}
    </button>

    <div x-show="terbuka" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-arang-900/60 backdrop-blur-sm p-4"
         @keydown.escape.window="terbuka = false">
        <div class="w-full max-w-md rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 shadow-xl transition-colors" @click.outside="terbuka = false">
            <h2 class="text-base font-semibold text-netral-900 dark:text-netral-50">{{ $judul }}</h2>
            <p class="mt-2 text-sm text-netral-600 dark:text-netral-300">{{ $pesan }}</p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="terbuka = false"
                        class="rounded border border-netral-200 dark:border-arang-600 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700 transition-colors">
                    Batal
                </button>
                <button type="button" @click="document.getElementById('{{ $formId }}').submit()"
                        class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                    {{ $labelKonfirmasi }}
                </button>
            </div>
        </div>
    </div>
</div>