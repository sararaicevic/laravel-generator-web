<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold leading-tight text-zinc-50">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="app-container space-y-6">
            <div class="ui-card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="ui-card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="ui-card p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
