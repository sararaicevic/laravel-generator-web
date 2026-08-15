<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold leading-tight text-zinc-50">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="app-container">
            <div class="ui-card p-6 sm:p-8">
                <div class="grid gap-8 lg:grid-cols-[1fr_320px] lg:items-center">
                    <div>
                        <p class="ui-pill">Workspace</p>
                        <h3 class="mt-5 text-3xl font-bold text-zinc-50">DSL generator za Laravel aplikacije.</h3>
                        <p class="mt-3 max-w-2xl text-zinc-400">
                            Upravljaj specifikacijama, prati status generisanja i preuzmi spreman ZIP kada projekat prođe obradu.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                        <a class="ui-button-primary" href="{{ route('generator.index') }}">Otvori projekte</a>
                        <a class="ui-button-secondary" href="{{ route('generator.create') }}">Nova specifikacija</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
