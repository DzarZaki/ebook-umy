<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Pustaka Dosen') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#5f4231">
<link rel="apple-touch-icon" href="/images/icon-192.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:400,600,700|karla:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-kabut-50">
        @include('layouts.navigation')

        @isset($header)
            <header class="border-b border-kabut-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main>
            {{ $slot }}
        </main>

        <footer class="border-t border-kabut-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 text-xs text-kabut-500 sm:px-6 lg:px-8">
                Pustaka Dosen &middot; Dikelola mandiri oleh dosen pengampu
            </div>
        </footer>
    </div>
</body>
</html>