<nav x-data="{ open: false }" class="border-b border-[#E2E8F0] bg-white/85 backdrop-blur-xl">
    <div class="app-container">
        <div class="flex h-16 justify-between">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-[#1E293B]">
                    <x-application-logo class="block h-10 w-10 text-[#6366F1]" />
                    <span class="hidden text-sm font-bold sm:block">Laravel DSL</span>
                </a>

                <div class="hidden gap-2 sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('generator.create')" :active="request()->routeIs('generator.create')">
                        {{ __('New Project') }}
                    </x-nav-link>
                    <x-nav-link :href="route('generator.index')" :active="request()->routeIs('generator.index', 'generator.show', 'generator.edit')">
                        {{ __('My Projects') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-md border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-semibold text-[#1E293B] shadow-sm transition hover:bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-[#E0E7FF] text-xs font-bold text-[#6366F1]">
                                {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-[#64748B]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-[#E2E8F0] bg-white text-[#64748B] transition hover:bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-[#6366F1]">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-[#E2E8F0] bg-white sm:hidden">
        <div class="space-y-2 px-4 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('generator.create')" :active="request()->routeIs('generator.create')">
                {{ __('New Project') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('generator.index')" :active="request()->routeIs('generator.index', 'generator.show', 'generator.edit')">
                {{ __('My Projects') }}
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-[#E2E8F0] px-4 py-4">
            <div class="font-semibold text-[#1E293B]">{{ Auth::user()->name }}</div>
            <div class="text-sm text-[#64748B]">{{ Auth::user()->email }}</div>

            <div class="mt-3 space-y-2">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
