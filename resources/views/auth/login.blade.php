<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Surel -->
        <div>
            <x-input-label for="email" value="Surel" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Kata Sandi -->
        <div class="mt-5">
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Ingat Saya -->
        <div class="mt-5">
            <label for="remember_me" class="inline-flex cursor-pointer items-center">
                <input id="remember_me" type="checkbox"
                       class="rounded border-kabut-300 text-jingga-600 shadow-sm focus:ring-jingga-500"
                       name="remember">
                <span class="ms-2 text-sm text-kabut-600">Ingat saya</span>
            </label>
        </div>

        <div class="mt-7 flex items-center justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm text-kabut-500 underline-offset-2 hover:text-kabut-900 hover:underline transition-colors duration-150 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-jingga-500"
                   href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif

            <x-primary-button class="ml-auto">
                Masuk
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
