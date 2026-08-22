<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Ubah Buku</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-buku" method="POST" action="{{ route('admin.buku.update', $buku) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.buku._form', ['buku' => $buku])
                </form>

                <div class="mt-8 flex items-center justify-end gap-3 border-t border-netral-200 dark:border-arang-600 pt-6">
                    <a href="{{ route('admin.buku.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40">Batal</a>

                    <x-tombol-konfirmasi form-id="form-buku" judul="Simpan Perubahan"
                        pesan="Perubahan data buku akan langsung berlaku. Lanjutkan?"
                        label="Simpan Perubahan" label-konfirmasi="Ya, Simpan" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>