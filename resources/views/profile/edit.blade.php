<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-netral-900 dark:text-netral-50 leading-tight">
            Profil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-arang-700/50 border border-netral-200 dark:border-arang-600 sm:rounded-lg shadow-sm dark:shadow-none transition-colors">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-arang-700/50 border border-netral-200 dark:border-arang-600 sm:rounded-lg shadow-sm dark:shadow-none transition-colors">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-arang-700/50 border border-netral-200 dark:border-arang-600 sm:rounded-lg shadow-sm dark:shadow-none transition-colors">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
