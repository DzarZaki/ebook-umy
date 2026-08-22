<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Unggah Buku</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-buku" method="POST" action="{{ route('admin.buku.store') }}" enctype="multipart/form-data">
                    @csrf
                    @include('admin.buku._form', ['buku' => null])
                </form>

                <div class="mt-8 flex items-center justify-end gap-3 border-t border-netral-200 dark:border-arang-600 pt-6">
                    <a href="{{ route('admin.buku.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">Batal</a>

                    <x-tombol-konfirmasi form-id="form-buku" judul="Unggah Buku"
                        pesan="Berkas PDF akan diunggah dan buku ditambahkan ke koleksi. Lanjutkan?"
                        label="Unggah" label-konfirmasi="Ya, Unggah" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>