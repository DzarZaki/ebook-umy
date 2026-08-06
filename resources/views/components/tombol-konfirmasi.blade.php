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
         class="fixed inset-0 z-50 flex items-center justify-center bg-kabut-900/60 p-4"
         @keydown.escape.window="terbuka = false">
        <div class="w-full max-w-md rounded-sm bg-white p-6 shadow-xl" @click.outside="terbuka = false">
            <h2 class="text-base font-semibold text-kabut-900">{{ $judul }}</h2>
            <p class="mt-2 text-sm text-kabut-600">{{ $pesan }}</p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="terbuka = false"
                        class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                    Batal
                </button>
                <button type="button" @click="document.getElementById('{{ $formId }}').submit()"
                        class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                    {{ $labelKonfirmasi }}
                </button>
            </div>
        </div>
    </div>
</div>