<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel Generator') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|inter:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <main class="app-shell min-h-screen">
            <div class="app-container flex min-h-screen flex-col">
                <header class="flex items-center justify-between py-6">
                    <a href="/" class="flex items-center gap-3 text-zinc-100">
                        <x-application-logo class="h-11 w-11 text-emerald-300" />
                        <span class="font-bold">Laravel Generator</span>
                    </a>

                    @if (Route::has('login'))
                        <div class="flex items-center gap-3">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="ui-button-secondary">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="ui-button-secondary">Log in</a>
                            @endauth
                        </div>
                    @endif
                </header>

                <section class="grid flex-1 gap-10 py-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                    <div>
                        <p class="ui-pill">Laravel generator</p>
                        <h1 class="mt-7 max-w-3xl text-5xl font-bold leading-tight text-zinc-50 sm:text-6xl">
                            Brže od ideje do Laravel projekta.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-400">
                            Unesi naziv aplikacije, dodaj modele i polja, a generator priprema strukturu projekta za preuzimanje.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('generator.index') }}" class="ui-button-primary">Otvori generator</a>
                            @else
                                <a href="{{ route('login') }}" class="ui-button-primary">Počni rad</a>
                            @endauth
                        </div>
                    </div>

                    <div class="ui-card p-5">
                        <div class="space-y-3">
                            <div class="ui-card-soft p-4">
                                <div class="text-sm font-semibold text-zinc-50">1. Naziv aplikacije</div>
                                <p class="mt-1 text-sm text-zinc-400">Postavi ime projekta koji želiš da generišeš.</p>
                            </div>
                            <div class="ui-card-soft p-4">
                                <div class="text-sm font-semibold text-zinc-50">2. Modeli i polja</div>
                                <p class="mt-1 text-sm text-zinc-400">Dodaj entitete, tipove podataka i osnovna pravila.</p>
                            </div>
                            <div class="ui-card-soft p-4">
                                <div class="text-sm font-semibold text-zinc-50">3. Download projekta</div>
                                <p class="mt-1 text-sm text-zinc-400">Preuzmi ZIP sa generisanim Laravel fajlovima.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
