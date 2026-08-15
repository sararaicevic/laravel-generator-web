<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Laravel Generator
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div>
                        <div class="text-lg font-medium">Generisanje Laravel aplikacije iz DSL specifikacije</div>
                        <div class="text-sm text-gray-600">Definiši aplikaciju, entitete i polja; sistem generiše modele, kontrolere, migracije, rute i Blade prikaze.</div>
                    </div>

                    <form method="POST" action="{{ route('generator.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" value="Naziv projekta" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="dsl" value="DSL specifikacija" />
                            <textarea id="dsl" name="dsl" rows="18" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" required>{{ old('dsl', "app InventorySystem {\n  entity Product {\n    name: string required\n    sku: string required unique\n    description: text nullable\n    price: decimal required\n    active: boolean\n  }\n\n  entity Category {\n    title: string required unique\n    description: text nullable\n  }\n}") }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('dsl')" />
                            <div class="mt-2 text-xs text-gray-600">
                                Sintaksa: <span class="font-mono">app Naziv { entity Entitet { polje: tip required unique } }</span>.
                                Podržani tipovi: string, text, integer, bigInteger, decimal, boolean, date, datetime, email, password.
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Pokreni generisanje</x-primary-button>
                            <a class="text-sm text-gray-600 underline" href="{{ route('dashboard') }}">Nazad</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
