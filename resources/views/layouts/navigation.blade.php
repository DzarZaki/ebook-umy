<nav x-data="{ terbuka: false }" class="border-b border-kabut-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">

            {{-- ===== Sisi kiri: logo dan menu utama ===== --}}
            <div class="flex">

                {{-- Logo aplikasi, mengarah ke beranda sesuai peran pengguna. --}}
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.svg') }}" alt="Logo {{ config('app.name') }}" class="h-8 w-8">
                        <span class="font-display text-lg font-semibold whitespace-nowrap text-sepia-800">
                            {{ config('app.name') }}
                        </span>
                    </a>
                </div>

                {{-- Menu utama versi lebar. Disembunyikan di bawah 1024px agar tidak berdesakan. --}}
                <div class="hidden space-x-6 lg:-my-px lg:ms-10 lg:flex">

                    {{-- Menu Super Admin: pengaturan prodi dan akun dosen. --}}
                    @if (auth()->user()->isSuperAdmin())
                        <x-nav-link :href="route('superadmin.dashboard')" :active="request()->routeIs('superadmin.dashboard')">
                            Beranda
                        </x-nav-link>
                        <x-nav-link :href="route('superadmin.prodi.index')" :active="request()->routeIs('superadmin.prodi.*')">
                            Program Studi
                        </x-nav-link>
                        <x-nav-link :href="route('superadmin.dosen.index')" :active="request()->routeIs('superadmin.dosen.*')">
                            Akun Dosen
                        </x-nav-link>
                    @endif

                    {{-- Menu Dosen: koleksi, kategori, statistik, mahasiswa, dan katalog. --}}
                    @if (auth()->user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            Beranda
                        </x-nav-link>
                        <x-nav-link :href="route('admin.buku.index')" :active="request()->routeIs('admin.buku.*')">
                            Koleksi Buku
                        </x-nav-link>
                        <x-nav-link :href="route('admin.kategori.index')" :active="request()->routeIs('admin.kategori.*')">
                            Kategori
                        </x-nav-link>
                        <x-nav-link :href="route('admin.statistik')" :active="request()->routeIs('admin.statistik')">
                            Statistik
                        </x-nav-link>
                        <x-nav-link :href="route('admin.mahasiswa.index')" :active="request()->routeIs('admin.mahasiswa.*')">
                            Mahasiswa
                        </x-nav-link>
                        <x-nav-link :href="route('katalog.index')" :active="request()->routeIs('katalog.*')">
                            Katalog
                        </x-nav-link>
                    @endif

                    {{-- Menu Mahasiswa: hanya katalog bacaan. --}}
                    @if (auth()->user()->isMahasiswa())
                        <x-nav-link :href="route('katalog.index')" :active="request()->routeIs('katalog.*')">
                            Katalog
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- ===== Sisi kanan: tombol pasang dan menu akun ===== --}}
            <div class="hidden lg:ms-6 lg:flex lg:items-center">

                {{-- Tombol pemasangan PWA. Disembunyikan sampai peramban menyatakan dukungannya. --}}
                <button id="tombol-pasang" type="button"
                        class="me-4 hidden whitespace-nowrap border border-jingga-600 px-3 py-1.5 text-xs font-semibold text-jingga-700 transition hover:bg-jingga-50">
                    Pasang
                </button>

                {{-- Menu gulung berisi identitas pengguna, tautan profil, dan tombol keluar. --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 text-sm font-medium text-kabut-600 transition hover:text-sepia-800 focus:outline-none">
                            <span class="flex h-8 w-8 items-center justify-center bg-sepia-700 text-xs font-semibold text-white">
                                {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="text-start leading-tight">
                                <span class="block whitespace-nowrap font-semibold text-sepia-800">
                                    {{ auth()->user()->name }}
                                </span>
                                <span class="block whitespace-nowrap text-xs text-kabut-500">
                                    @if (auth()->user()->isSuperAdmin())
                                        Super Admin
                                    @elseif (auth()->user()->isAdmin())
                                        Dosen{{ auth()->user()->prodi ? ' · '.auth()->user()->prodi->name : '' }}
                                    @else
                                        {{ auth()->user()->prodi->name ?? 'Mahasiswa' }}
                                    @endif
                                </span>
                            </span>
                            <svg class="h-4 w-4 text-kabut-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil Saya
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                             onclick="event.preventDefault(); this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Tombol hamburger, tampil di bawah 1024px. --}}
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="terbuka = ! terbuka"
                        class="inline-flex items-center justify-center p-2 text-kabut-500 transition hover:bg-kabut-50 hover:text-sepia-800 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': terbuka, 'inline-flex': ! terbuka }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! terbuka, 'inline-flex': terbuka }" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Menu tanggap untuk layar sempit ===== --}}
    <div :class="{ 'block': terbuka, 'hidden': ! terbuka }" class="hidden border-t border-kabut-200 lg:hidden">
        <div class="space-y-1 pt-2 pb-3">

            {{-- Tautan Super Admin versi layar sempit. --}}
            @if (auth()->user()->isSuperAdmin())
                <x-responsive-nav-link :href="route('superadmin.dashboard')" :active="request()->routeIs('superadmin.dashboard')">
                    Beranda
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('superadmin.prodi.index')" :active="request()->routeIs('superadmin.prodi.*')">
                    Program Studi
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('superadmin.dosen.index')" :active="request()->routeIs('superadmin.dosen.*')">
                    Akun Dosen
                </x-responsive-nav-link>
            @endif

            {{-- Tautan Dosen versi layar sempit. --}}
            @if (auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    Beranda
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.buku.index')" :active="request()->routeIs('admin.buku.*')">
                    Koleksi Buku
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.kategori.index')" :active="request()->routeIs('admin.kategori.*')">
                    Kategori
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.statistik')" :active="request()->routeIs('admin.statistik')">
                    Statistik
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.mahasiswa.index')" :active="request()->routeIs('admin.mahasiswa.*')">
                    Mahasiswa
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('katalog.index')" :active="request()->routeIs('katalog.*')">
                    Katalog
                </x-responsive-nav-link>
            @endif

            {{-- Tautan Mahasiswa versi layar sempit. --}}
            @if (auth()->user()->isMahasiswa())
                <x-responsive-nav-link :href="route('katalog.index')" :active="request()->routeIs('katalog.*')">
                    Katalog
                </x-responsive-nav-link>
            @endif
        </div>

        {{-- Blok identitas, tombol pasang, profil, dan tombol keluar. --}}
        <div class="border-t border-kabut-200 pt-4 pb-3">
            <div class="px-4">
                <div class="font-semibold text-sepia-800">{{ auth()->user()->name }}</div>
                <div class="text-sm text-kabut-500">{{ auth()->user()->email }}</div>
                @if (auth()->user()->prodi)
                    <div class="text-xs text-kabut-500">{{ auth()->user()->prodi->name }}</div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <button id="tombol-pasang-mobile" type="button"
                        class="hidden w-full px-4 py-2 text-start text-base font-semibold text-jingga-700 transition hover:bg-kabut-50">
                    Pasang Aplikasi
                </button>

                <x-responsive-nav-link :href="route('profile.edit')">
                    Profil Saya
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                                           onclick="event.preventDefault(); this.closest('form').submit();">
                        Keluar
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>