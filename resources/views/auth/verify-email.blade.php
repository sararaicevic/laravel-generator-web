<x-guest-layout>
    <div class="mb-7">
        <p class="ui-pill">Verify email</p>
        <h1 class="mt-4 text-3xl font-bold text-zinc-50">Verifikacija emaila</h1>
    </div>

    <div class="mb-4 text-sm leading-6 text-zinc-400">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-md border border-emerald-300/30 bg-emerald-300/10 p-3 text-sm font-medium text-emerald-200">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="ui-link rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
