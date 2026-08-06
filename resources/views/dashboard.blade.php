<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-kabut-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-sm">
                <div class="p-6 text-kabut-900">
                    <p class="text-lg">Selamat datang, <strong>{{ auth()->user()->name }}</strong>.</p>

                    <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm text-kabut-500">Email</dt>
                            <dd class="font-medium">{{ auth()->user()->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-kabut-500">Peran</dt>
                            <dd class="font-medium">
                                @switch(auth()->user()->role)
                                    @case(\App\Models\User::ROLE_SUPERADMIN) Super Admin @break
                                    @case(\App\Models\User::ROLE_ADMIN) Admin / Dosen @break
                                    @default Mahasiswa
                                @endswitch
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-kabut-500">Program Studi</dt>
                            <dd class="font-medium">{{ auth()->user()->prodi?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>