<x-guest-layout>
    <div class="mb-7">
        <p class="ui-pill">Verify email</p>
        <h1 class="mt-4 text-3xl font-bold text-[#1E293B]">Verify Email</h1>
    </div>

    <div class="mb-4 text-sm leading-6 text-[#64748B]">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-md border border-[#BBF7D0] bg-[#DCFCE7] p-3 text-sm font-medium text-[#047857]">
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

            <button type="submit" class="ui-link rounded-md focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
