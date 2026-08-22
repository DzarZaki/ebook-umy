{{-- Bilah navigasi kini lengket di puncak layar dan tembus pandang.

     Putih pekat di atas dasar kertas akan terlihat seperti balok terpisah
     yang mengambang. Kaca buram membiarkan butiran dan gradasi dasar
     terlihat samar menembusnya, sehingga bilah ini terasa bagian dari
     bahan yang sama — hanya lapisan yang lebih dekat ke mata.

     'digulir' dipantau dari posisi gulir jendela. Selama halaman masih di
     puncak, bilah ini nyaris tak berbatas; begitu isi mulai lewat di
     bawahnya, bayangan tipis muncul untuk memisahkan keduanya. --}}
<nav x-data="{ terbuka: false, digulir: false }"
     @scroll.window="digulir = window.scrollY > 8"
     :class="digulir ? 'shadow-[0_4px_20px_-10px_rgba(0,0,0,0.08)] dark:shadow-[0_12px_30px_-26px_rgba(0,0,0,0.9)]' : ''"
     class="sticky top-0 z-40 border-b border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/90 backdrop-blur-md transition-shadow duration-300">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">

            {{-- ===== Sisi kiri: logo dan menu utama ===== --}}
            <div class="flex">

                {{-- Logo aplikasi, mengarah ke beranda sesuai peran pengguna. --}}
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.svg') }}" alt="Logo {{ config('app.name') }}" class="h-8 w-8">
                        <span class="font-display text-lg font-semibold whitespace-nowrap text-netral-900 dark:text-netral-50">
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

                    {{-- Menu Mahasiswa: rak pribadi, katalog, lalu koleksi tersimpan. --}}
                    @if (auth()->user()->isMahasiswa())
                        <x-nav-link :href="route('beranda.saya')" :active="request()->routeIs('beranda.saya')">
                            Beranda
                        </x-nav-link>
                        <x-nav-link :href="route('katalog.index')" :active="request()->routeIs('katalog.*')">
                            Katalog
                        </x-nav-link>
                        <x-nav-link :href="route('koleksi.index')" :active="request()->routeIs('koleksi.*')">
                            Koleksi Saya
                        </x-nav-link>
                    @endif
                </div>
            </div>

            {{-- ===== Sisi kanan: pemilih tema, tombol pasang, dan menu akun ===== --}}
            <div class="hidden lg:ms-6 lg:flex lg:items-center gap-3">

                {{-- Pemilih Mode (Terang / Gelap / Sistem) --}}
                <div x-data class="relative">
                    <button @click="$store.theme.setMode($store.theme.mode === 'dark' ? 'light' : ($store.theme.mode === 'light' ? 'system' : 'dark'))"
                            type="button"
                            :title="'Mode: ' + $store.theme.mode"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-netral-200 dark:border-arang-600 bg-netral-50 dark:bg-arang-700 text-netral-600 dark:text-netral-300 transition-colors hover:text-jingga-600 dark:hover:text-jingga-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600">
                        {{-- Ikon Matahari (Light) --}}
                        <svg x-show="$store.theme.mode === 'light'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        {{-- Ikon Bulan (Dark) --}}
                        <svg x-show="$store.theme.mode === 'dark'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        {{-- Ikon Monitor (Sistem) --}}
                        <svg x-show="$store.theme.mode === 'system'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>

                {{-- Tombol pemasangan PWA. Disembunyikan sampai peramban menyatakan dukungannya. --}}
                <button id="tombol-pasang" type="button"
                        class="hidden whitespace-nowrap border border-jingga-600 dark:border-jingga-400 px-3 py-1.5 text-xs font-semibold text-jingga-600 dark:text-jingga-400 transition hover:bg-jingga-50 dark:hover:bg-jingga-500/10">
                    Pasang
                </button>

                {{-- Menu gulung berisi identitas pengguna, tautan profil, dan tombol keluar. --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 text-sm font-medium text-netral-600 dark:text-netral-300 transition hover:text-netral-900 dark:hover:text-netral-50 focus:outline-none">
                            <span class="flex h-8 w-8 items-center justify-center rounded-sm bg-jingga-600 dark:bg-jingga-500 text-xs font-semibold text-white">
                                {{ Str::upper(Str::substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="text-start leading-tight">
                                <span class="block whitespace-nowrap font-semibold text-netral-900 dark:text-netral-50">
                                    {{ auth()->user()->name }}
                                </span>
                                <span class="block whitespace-nowrap text-xs text-netral-500 dark:text-netral-400">
                                    @if (auth()->user()->isSuperAdmin())
                                        Super Admin
                                    @elseif (auth()->user()->isAdmin())
                                        Dosen{{ auth()->user()->prodi ? ' · '.auth()->user()->prodi->name : '' }}
                                    @else
                                        {{ auth()->user()->prodi->name ?? 'Mahasiswa' }}
                                    @endif
                                </span>
                            </span>
                            <svg class="h-4 w-4 text-netral-400" viewBox="0 0 20 20" fill="currentColor">
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
                        class="inline-flex items-center justify-center p-2 text-netral-600 dark:text-netral-400 transition hover:bg-netral-100 dark:hover:bg-arang-700 hover:text-netral-900 dark:hover:text-netral-50 focus:outline-none">
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
    <div :class="{ 'block': terbuka, 'hidden': ! terbuka }"
         class="hidden max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-netral-200 dark:border-arang-600 bg-white/95 dark:bg-arang-800/95 backdrop-blur-md lg:hidden">
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
                <x-responsive-nav-link :href="route('beranda.saya')" :active="request()->routeIs('beranda.saya')">
                    Beranda
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('katalog.index')" :active="request()->routeIs('katalog.*')">
                    Katalog
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('koleksi.index')" :active="request()->routeIs('koleksi.*')">
                    Koleksi Saya
                </x-responsive-nav-link>
            @endif
        </div>

        {{-- Blok identitas, pemilih tema mobile, tombol pasang, profil, dan tombol keluar. --}}
        <div class="border-t border-netral-200 dark:border-arang-600 pt-4 pb-3">
            <div class="px-4 flex items-center justify-between">
                <div>
                    <div class="font-semibold text-netral-900 dark:text-netral-50">{{ auth()->user()->name }}</div>
                    <div class="text-sm text-netral-500 dark:text-netral-400">{{ auth()->user()->email }}</div>
                    @if (auth()->user()->prodi)
                        <div class="text-xs text-netral-500 dark:text-netral-400">{{ auth()->user()->prodi->name }}</div>
                    @endif
                </div>

                {{-- Pemilih tema mobile --}}
                <div x-data class="flex gap-1 border border-netral-200 dark:border-arang-600 p-1 rounded">
                    <button @click="$store.theme.setMode('light')"
                            :class="$store.theme.mode === 'light' ? 'bg-jingga-600 text-white' : 'text-netral-500 hover:text-netral-900 dark:text-netral-400'"
                            class="p-1.5 rounded transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <button @click="$store.theme.setMode('dark')"
                            :class="$store.theme.mode === 'dark' ? 'bg-jingga-600 text-white' : 'text-netral-500 hover:text-netral-900 dark:text-netral-400'"
                            class="p-1.5 rounded transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <button @click="$store.theme.setMode('system')"
                            :class="$store.theme.mode === 'system' ? 'bg-jingga-600 text-white' : 'text-netral-500 hover:text-netral-900 dark:text-netral-400'"
                            class="p-1.5 rounded transition">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <button id="tombol-pasang-mobile" type="button"
                        class="hidden w-full px-4 py-2 text-start text-base font-semibold text-jingga-600 dark:text-jingga-400 transition hover:bg-netral-100 dark:hover:bg-arang-700">
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