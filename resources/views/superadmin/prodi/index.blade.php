<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-kabut-800">Program Studi</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <div class="mb-4 flex justify-end">
                <a href="{{ route('superadmin.prodi.create') }}"
                   class="rounded-sm bg-jingga-600 px-4 py-2 text-sm font-semibold text-white hover:bg-jingga-700">
                    Tambah Program Studi
                </a>
            </div>

            <div class="overflow-hidden rounded-sm border border-kabut-200 bg-white">
                <table class="min-w-full divide-y divide-kabut-200 text-sm">
                    <thead class="bg-kabut-50 text-left text-kabut-600">
                        <tr>
                            <th class="px-4 py-3 font-medium">Nama Program Studi</th>
                            <th class="px-4 py-3 font-medium">Slug</th>
                            <th class="px-4 py-3 font-medium">Pengguna</th>
                            <th class="px-4 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-kabut-100">
                        @forelse ($daftarProdi as $prodi)
                            <tr>
                                <td class="px-4 py-3 font-medium text-kabut-900">{{ $prodi->name }}</td>
                                <td class="px-4 py-3 text-kabut-500">{{ $prodi->slug }}</td>
                                <td class="px-4 py-3 text-kabut-700">{{ $prodi->users_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('superadmin.prodi.edit', $prodi) }}"
                                           class="rounded-sm border border-kabut-300 px-3 py-1.5 text-sm font-medium text-kabut-700 hover:bg-kabut-50">
                                            Ubah
                                        </a>

                                        <x-tombol-hapus
                                            :action="route('superadmin.prodi.destroy', $prodi)"
                                            judul="Hapus Program Studi"
                                            :pesan="'Program studi &quot;'.$prodi->name.'&quot; akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.'" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-kabut-500">
                                    Belum ada program studi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $daftarProdi->links() }}</div>
        </div>
    </div>
</x-app-layout>