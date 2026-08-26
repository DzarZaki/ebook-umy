{{-- Menampilkan pesan sukses dan daftar galat validasi secara seragam. --}}
@if (session('status'))
    <div class="mb-6 rounded-sm border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-300">
        {{ session('status') }}
    </div>
@endif

@if (session('gagal'))
    <div class="mb-6 border-l-4 border-red-600 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-300">
        {{ session('gagal') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-sm border border-red-200 dark:border-red-800/60 bg-red-50 dark:bg-red-950/40 px-4 py-3 text-sm text-red-800 dark:text-red-300">
        <ul class="list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    </div>
@endif