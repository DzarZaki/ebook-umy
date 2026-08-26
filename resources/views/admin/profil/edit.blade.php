<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="font-display text-xl font-semibold leading-tight text-netral-900 dark:text-netral-50">
                Profil &amp; Branding Dosen
            </h1>
            <a href="{{ route('beranda') }}" target="_blank"
               class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-jingga-600 dark:text-jingga-400 hover:text-jingga-700 dark:hover:text-jingga-300">
                Lihat di Halaman Muka &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-alert-status />

            <form method="POST" action="{{ route('admin.profil-dosen.update') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')

                {{-- 1. FOTO PROFIL & IDENTITAS UTAMA --}}
                <div class="rounded-xl border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/80 p-6 sm:p-8 shadow-sm backdrop-blur-sm transition-colors">
                    <h3 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Foto Profil &amp; Nama Akademik</h3>
                    <p class="mt-1 text-sm text-netral-600 dark:text-netral-400">
                        Foto dan nama ini akan ditampilkan di kartu pengampu pada halaman depan situs.
                    </p>

                    <div class="mt-6 flex flex-col gap-6 sm:flex-row sm:items-start">
                        {{-- Preview Foto --}}
                        <div class="flex shrink-0 flex-col items-center gap-3">
                            <div class="relative h-32 w-32 overflow-hidden rounded-2xl border-2 border-netral-200 dark:border-arang-600 bg-netral-100 dark:bg-arang-700 shadow-inner">
                                @if ($profil->photoUrl())
                                    <img src="{{ $profil->photoUrl() }}" alt="Foto {{ $user->name }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center font-display text-4xl font-semibold text-jingga-600 dark:text-jingga-400">
                                        {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            @if ($profil->photo_path)
                                <x-tombol-hapus
                                    :action="route('admin.profil-dosen.hapus-foto')"
                                    judul="Hapus Foto Profil"
                                    pesan="Foto akan lenyap dari kartu pengampu di halaman depan. Anda tetap dapat mengunggah foto baru kapan saja."
                                    label="Hapus Foto" />
                            @endif
                        </div>

                        {{-- Input Form Nama & Gelar --}}
                        <div class="flex-1 grid gap-4 sm:grid-cols-12">
                            <div class="sm:col-span-3">
                                <x-input-label for="title_prefix" value="Gelar Depan" />
                                <x-text-input id="title_prefix" name="title_prefix" type="text"
                                              class="mt-1 block w-full text-sm"
                                              :value="old('title_prefix', $profil->title_prefix)"
                                              placeholder="Contoh: Dr. / Prof." />
                                <x-input-error :messages="$errors->get('title_prefix')" class="mt-1" />
                            </div>

                            <div class="sm:col-span-6">
                                <x-input-label for="name" value="Nama Lengkap *" />
                                <x-text-input id="name" name="name" type="text"
                                              class="mt-1 block w-full text-sm font-semibold"
                                              :value="old('name', $user->name)" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                            </div>

                            <div class="sm:col-span-3">
                                <x-input-label for="title_suffix" value="Gelar Belakang" />
                                <x-text-input id="title_suffix" name="title_suffix" type="text"
                                              class="mt-1 block w-full text-sm"
                                              :value="old('title_suffix', $profil->title_suffix)"
                                              placeholder="Contoh: M.Kom., Ph.D." />
                                <x-input-error :messages="$errors->get('title_suffix')" class="mt-1" />
                            </div>

                            <div class="sm:col-span-12">
                                <x-input-label for="photo" value="Unggah Foto Profil Baru (Maks 2MB, JPG/PNG/WebP)" />
                                <input id="photo" name="photo" type="file" accept="image/*"
                                       class="mt-1 block w-full text-sm text-netral-500 dark:text-netral-400 file:mr-4 file:rounded file:border-0 file:bg-netral-100 dark:file:bg-arang-700 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-netral-700 dark:file:text-netral-300 hover:file:bg-netral-200 dark:hover:file:bg-arang-600" />
                                <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. JABATAN & BIDANG KEAHLIAN --}}
                <div class="rounded-xl border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/80 p-6 sm:p-8 shadow-sm backdrop-blur-sm transition-colors">
                    <h3 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Identitas Akademik &amp; Keahlian</h3>
                    <p class="mt-1 text-sm text-netral-600 dark:text-netral-400">
                        Informasi posisi akademik dan bidang riset pengampu.
                    </p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="nidn" value="NIDN / NIP / NIK" />
                            <x-text-input id="nidn" name="nidn" type="text"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('nidn', $profil->nidn)"
                                          placeholder="Nomor Induk Dosen..." />
                            <x-input-error :messages="$errors->get('nidn')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="academic_position" value="Jabatan Fungsional / Peran" />
                            <x-text-input id="academic_position" name="academic_position" type="text"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('academic_position', $profil->academic_position)"
                                          placeholder="Contoh: Lektor Kepala / Dosen Pengampu" />
                            <x-input-error :messages="$errors->get('academic_position')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="expertise" value="Bidang Keahlian / Fokus Riset (Pisahkan dengan koma)" />
                            <x-text-input id="expertise" name="expertise" type="text"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('expertise', $profil->expertise)"
                                          placeholder="Contoh: Kecerdasan Buatan, Rekayasa Web, Sistem Basis Data, Cloud Computing" />
                            <x-input-error :messages="$errors->get('expertise')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- 3. SAMBUTAN & KUTIPAN MOTTO --}}
                <div class="rounded-xl border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/80 p-6 sm:p-8 shadow-sm backdrop-blur-sm transition-colors">
                    <h3 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Sambutan &amp; Kutipan Inspiratif</h3>
                    <p class="mt-1 text-sm text-netral-600 dark:text-netral-400">
                        Pesan pengantar dari Anda kepada para mahasiswa yang mengakses buku ini.
                    </p>

                    <div class="mt-6 space-y-5">
                        <div>
                            <x-input-label for="bio" value="Sambutan Dosen untuk Mahasiswa (Tampil di Landing Page)" />
                            <textarea id="bio" name="bio" rows="4"
                                      class="mt-1 block w-full rounded border-netral-200 dark:border-arang-600 bg-white dark:bg-arang-700 text-sm text-netral-900 dark:text-netral-100 shadow-sm focus:border-jingga-600 dark:focus:border-jingga-400 focus:ring-1 focus:ring-jingga-500"
                                      placeholder="Tuliskan pesan sambutan singkat kepada mahasiswa...">{{ old('bio', $profil->bio) }}</textarea>
                            <x-input-error :messages="$errors->get('bio')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="quote" value="Kutipan / Motto Pengajaran (Opsional)" />
                            <x-text-input id="quote" name="quote" type="text"
                                          class="mt-1 block w-full text-sm italic"
                                          :value="old('quote', $profil->quote)"
                                          placeholder="Contoh: Membaca adalah jembatan pemahaman, bukan sekadar mengingat." />
                            <x-input-error :messages="$errors->get('quote')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- 4. TAUTAN PROFIL AKADEMIK --}}
                <div class="rounded-xl border border-netral-200 dark:border-arang-600 bg-white/80 dark:bg-arang-800/80 p-6 sm:p-8 shadow-sm backdrop-blur-sm transition-colors">
                    <h3 class="font-display text-lg font-semibold text-netral-900 dark:text-netral-50">Tautan Akademik &amp; Publikasi</h3>
                    <p class="mt-1 text-sm text-netral-600 dark:text-netral-400">
                        Tautan ke profil riset eksternal agar mahasiswa dapat membaca karya ilmiah Anda lainnya.
                    </p>

                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="google_scholar_url" value="Google Scholar URL" />
                            <x-text-input id="google_scholar_url" name="google_scholar_url" type="url"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('google_scholar_url', $profil->google_scholar_url)"
                                          placeholder="https://scholar.google.com/citations?user=..." />
                            <x-input-error :messages="$errors->get('google_scholar_url')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="scopus_url" value="Scopus / Sinta Profile URL" />
                            <x-text-input id="scopus_url" name="scopus_url" type="url"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('scopus_url', $profil->scopus_url)"
                                          placeholder="https://www.scopus.com/authid/detail.uri?..." />
                            <x-input-error :messages="$errors->get('scopus_url')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="linkedin_url" value="LinkedIn Profile URL" />
                            <x-text-input id="linkedin_url" name="linkedin_url" type="url"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('linkedin_url', $profil->linkedin_url)"
                                          placeholder="https://linkedin.com/in/..." />
                            <x-input-error :messages="$errors->get('linkedin_url')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="website_url" value="Website Pribadi / Blog Akademik URL" />
                            <x-text-input id="website_url" name="website_url" type="url"
                                          class="mt-1 block w-full text-sm"
                                          :value="old('website_url', $profil->website_url)"
                                          placeholder="https://dosen-pribadi.ac.id" />
                            <x-input-error :messages="$errors->get('website_url')" class="mt-1" />
                        </div>
                    </div>
                </div>

                {{-- 5. PENGATURAN TAMPILAN & TOMBOL SIMPAN --}}
                <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer text-sm font-medium text-netral-700 dark:text-netral-300">
                        <input type="checkbox" name="is_displayed" value="1"
                               class="rounded border-netral-300 dark:border-arang-600 text-jingga-600 focus:ring-jingga-500"
                               @checked(old('is_displayed', $profil->is_displayed ?? true))>
                        <span>Tampilkan profil ini di Halaman Muka (Landing Page)</span>
                    </label>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded bg-jingga-600 dark:bg-jingga-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-jingga-700 dark:hover:bg-jingga-600 btn-press focus:outline-none focus-visible:ring-2 focus-visible:ring-jingga-600">
                        Simpan Profil Dosen
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
