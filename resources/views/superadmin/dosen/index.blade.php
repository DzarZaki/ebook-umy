<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-kabut-800">Akun Dosen</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex justify-end">
                <a href="{{ route('superadmin.dosen.create') }}"
                   class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                    Tambah Dosen
                </a>
            </div>

            <div class="overflow-hidden rounded-sm border border-kabut-200 bg-white">
                <table class="min-w-full divide-y divide-kabut-200 text-sm">
                    <thead class="bg-kabut-50 text-left text-kabut-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama</th>
                            <th class="px-4 py-3 font-medium">Email</th>
                            <th class="px-4 py-3 font-medium">Program Studi</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-kabut-100">
                        @forelse ($daftarDosen as $dosen)
                            <tr>
                                <td class="px-4 py-3 font-medium text-kabut-900">{{ $dosen->name }}</td>
                                <td class="px-4 py-3 text-kabut-600">{{ $dosen->email }}</td>
                                <td class="px-4 py-3 text-kabut-600">{{ $dosen->prodi?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($dosen->is_active)
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-kabut-100 px-2 py-1 text-xs font-medium text-kabut-600">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('superadmin.dosen.edit', $dosen) }}"
                                           class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                                            Ubah
                                        </a>

                                        <x-tombol-hapus
                                            :action="route('superadmin.dosen.destroy', $dosen)"
                                            judul="Hapus Akun Dosen"
                                            :pesan="'Akun &quot;'.$dosen->name.'&quot; akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.'" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-kabut-500">
                                    Belum ada akun dosen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarDosen->links() }}</div>
        </div>
    </div>
</x-app-layout>