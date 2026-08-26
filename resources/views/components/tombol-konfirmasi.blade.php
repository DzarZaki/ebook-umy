{{--
    Tombol simpan yang memunculkan modal konfirmasi terlebih dahulu.
    Form divalidasi browser dulu, baru modal muncul.
    Fokus dikelola oleh data Alpine `modalFokus` (app.js).
--}}
@props([
    'form-id',
    'judul' => 'Konfirmasi Penyimpanan',
    'pesan' => 'Pastikan data yang Anda masukkan sudah benar sebelum disimpan.',
    'label' => 'Simpan',
    'labelKonfirmasi' => 'Ya, Simpan',
])

<div x-data="modalFokus" class="inline-block">
    <button
        type="button"
        @click="document.getElementById('{{ $formId }}').reportValidity() && buka()"
        class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-jingga-700 focus:outline-none focus:ring-2 focus:ring-jingga-500 focus:ring-offset-2"
    >
        {{ $label }}
    </button>

    <div x-show="terbuka" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-arang-900/60 backdrop-blur-sm p-4"
         @keydown.escape.window="tutup()"
         @keydown.tab="jagaTab($event)">
        <div class="w-full max-w-md rounded-lg border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-800 p-6 shadow-xl transition-colors focus:outline-none"
             @click.outside="tutup()"
             tabindex="-1" data-panel-fokus>
            <h2 class="text-base font-semibold text-netral-900 dark:text-netral-50">{{ $judul }}</h2>
            <p class="mt-2 text-sm text-netral-600 dark:text-netral-300">{{ $pesan }}</p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="tutup()"
                        class="rounded border border-netral-200 dark:border-arang-600 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700 transition-colors">
                    Batal
                </button>
                {{-- Dialog ditutup SEBELUM mengirim: pada formulir unggah
                     ber-XHR, galat validasi (422) tampil di kotak #galat-unggah
                     di halaman — ia tak terbaca bila overlay masih menutupi. --}}
                <button type="button" @click="tutup(); document.getElementById('{{ $formId }}').requestSubmit()"
                        class="rounded bg-jingga-600 dark:bg-jingga-500 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700 dark:hover:bg-jingga-600 shadow-sm transition-colors">
                    {{ $labelKonfirmasi }}
                </button>
            </div>
        </div>
    </div>
</div>
