<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">Unggah Buku</h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="border border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700/50 rounded-lg p-6 shadow-sm dark:shadow-none transition-colors">
                <form id="form-buku" method="POST" action="{{ route('admin.buku.store') }}" enctype="multipart/form-data" data-progres-unggah>
                    @csrf
                    @include('admin.buku._form', ['buku' => null])
                </form>

                {{-- Umpan balik unggahan: diisi oleh resources/js/unggah-buku.js --}}
                <div id="galat-unggah"
                     class="mt-6 hidden rounded-lg border border-red-200 dark:border-red-800/60 bg-red-50 dark:bg-red-950/40 p-4 text-sm text-red-700 dark:text-red-300"
                     role="alert"></div>

                <div id="progres-unggah" class="mt-8 hidden border-t border-netral-200 dark:border-arang-600 pt-6">
                    <div class="flex items-center justify-between text-sm font-medium text-netral-600 dark:text-netral-300">
                        <span id="progres-label">Mengunggah…</span>
                        <span id="progres-persen">0%</span>
                    </div>
                    <div id="progres-bar-lacak" class="mt-2 h-2 w-full overflow-hidden rounded-full bg-netral-200 dark:bg-arang-600"
                         role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                         aria-label="Kemajuan unggah berkas">
                        <div id="progres-bar" class="h-full w-0 rounded-full bg-jingga-600 dark:bg-jingga-500 transition-[width] duration-200 ease-out"></div>
                    </div>
                </div>

                <div id="footer-form-buku" class="mt-8 flex items-center justify-end gap-3 border-t border-netral-200 dark:border-arang-600 pt-6">
                    <a href="{{ route('admin.buku.index') }}"
                       class="rounded border border-netral-200 dark:border-arang-500 px-4 py-2 text-sm font-medium text-netral-700 dark:text-netral-300 hover:bg-netral-100 dark:hover:bg-arang-700/40 transition-colors">Batal</a>

                    <x-tombol-konfirmasi form-id="form-buku" judul="Unggah Buku"
                        pesan="Berkas PDF akan diunggah dan buku ditambahkan ke koleksi. Lanjutkan?"
                        label="Unggah" label-konfirmasi="Ya, Unggah" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>