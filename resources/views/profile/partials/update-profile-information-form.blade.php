<section>
    <header>
        <h2 class="text-lg font-medium text-kabut-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-kabut-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        {{-- Program studi hanya ditampilkan, tidak dapat diubah pengguna sendiri. --}}
@if ($user->prodi)
    <div>
        <x-input-label value="Program Studi" />

        <div class="mt-1 border border-kabut-200 bg-kabut-50 px-3 py-2 text-sm text-kabut-700">
            {{ $user->prodi->name }}
        </div>

        <p class="mt-1 text-xs text-kabut-500">
            @if ($user->isMahasiswa())
                Program studi ditentukan oleh kode akses saat pendaftaran dan hanya dapat diubah oleh dosen.
            @else
                Program studi akun dosen hanya dapat diubah oleh Super Admin.
            @endif
        </p>
    </div>
@endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-kabut-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
