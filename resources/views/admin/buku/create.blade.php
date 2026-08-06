<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-kabut-800">Unggah Buku</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-kabut-200 bg-white p-6">
                <form id="form-buku" method="POST" action="{{ route('admin.buku.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('admin.buku._form', ['buku' => null])
                </form>

                <div class="mt-8 flex items-center justify-end gap-3 border-t border-kabut-200 pt-6">
                    <a href="{{ route('admin.buku.index') }}"
                       class="rounded-sm border border-kabut-300 px-4 py-2 text-sm font-medium text-kabut-700 hover:bg-kabut-100">Batal</a>

                    <x-tombol-konfirmasi form-id="form-buku" judul="Unggah Buku"
                        pesan="Berkas PDF akan diunggah dan buku ditambahkan ke koleksi. Lanjutkan?"
                        label="Unggah" label-konfirmasi="Ya, Unggah" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>