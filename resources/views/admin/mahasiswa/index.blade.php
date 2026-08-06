<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-kabut-900">Mahasiswa Program Studi</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <x-alert-status />

        {{-- Pencarian --}}
        <form method="GET" action="{{ route('admin.mahasiswa.index') }}" class="mb-6 flex gap-2">
            <input type="text" name="cari" value="{{ $cari }}"
                   placeholder="Cari nama atau email mahasiswa"
                   class="w-full max-w-sm rounded-sm border-kabut-300 text-sm focus:border-jingga-500 focus:ring-jingga-500">
            <x-primary-button>Cari</x-primary-button>

            @if ($cari !== '')
                <a href="{{ route('admin.mahasiswa.index') }}"
                   class="inline-flex items-center px-3 text-sm text-kabut-600 underline hover:text-kabut-900">
                    Bersihkan
                </a>
            @endif
        </form>

        <div class="border border-kabut-200 bg-white">
            <table class="w-full text-sm">
                <thead class="border-b border-kabut-200 bg-kabut-50 text-left text-xs uppercase tracking-wide text-kabut-500">
                    <tr>
                        <th class="px-4 py-3">Nama Lengkap</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Terdaftar</th>
                        <th class="px-4 py-3 text-right">Tindakan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-kabut-100">
                    @forelse ($daftarMahasiswa as $mahasiswa)
                        <tr>
                            <td class="px-4 py-3 font-medium text-kabut-900">{{ $mahasiswa->name }}</td>
                            <td class="px-4 py-3 text-kabut-600">{{ $mahasiswa->email }}</td>
                            <td class="px-4 py-3">
                                @if ($mahasiswa->is_active)
                                    <span class="inline-block bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Aktif</span>
                                @else
                                    <span class="inline-block bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-kabut-500">{{ $mahasiswa->created_at->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.mahasiswa.edit', $mahasiswa) }}"
           class="text-sm text-jingga-700 underline hover:text-jingga-800">Ubah</a>

        <x-tombol-konfirmasi
            :form-id="'form-status-'.$mahasiswa->id"
            :label="$mahasiswa->is_active ? 'Nonaktifkan' : 'Aktifkan'"
            judul="Ubah Status Akun"
            :pesan="'Anda akan '.($mahasiswa->is_active ? 'menonaktifkan' : 'mengaktifkan').' akun '.$mahasiswa->name.'. Lanjutkan?'" />

        {{-- Komponen ini membuat formulir hapusnya sendiri, cukup diberi alamat tujuan. --}}
        <x-tombol-hapus
            :action="route('admin.mahasiswa.destroy', $mahasiswa)"
            judul="Hapus Akun Mahasiswa"
            :pesan="'Akun '.$mahasiswa->name.' akan dihapus permanen. Lanjutkan?'" />
    </div>

    {{-- Formulir tersembunyi khusus tombol ubah status. --}}
    <form id="form-status-{{ $mahasiswa->id }}" method="POST"
          action="{{ route('admin.mahasiswa.status', $mahasiswa) }}" class="hidden">
        @csrf @method('PATCH')
    </form>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-kabut-500">
                                Belum ada mahasiswa yang mendaftar dengan kode akses program studi Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $daftarMahasiswa->links() }}</div>
    </div>
</x-app-layout>