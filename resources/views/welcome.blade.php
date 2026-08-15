<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel DSL') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <main class="app-shell min-h-screen">
            <div class="app-container flex min-h-screen flex-col">
                <header class="flex items-center justify-between py-6">
                    <a href="/" class="flex items-center gap-3 text-[#1E293B]">
                        <x-application-logo class="h-11 w-11" />
                        <span class="font-bold">Laravel DSL</span>
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

                <section class="grid flex-1 gap-10 py-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                    <div>
                        <div class="flex items-center gap-4">
                            <x-application-logo class="h-16 w-16 sm:h-20 sm:w-20" />
                            <p class="ui-pill">Laravel application builder</p>
                        </div>
                        <h1 class="mt-7 max-w-3xl text-5xl font-bold leading-tight text-[#1E293B] sm:text-6xl">
                            Generate a Laravel app from models and relationships.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-[#64748B]">
                            Define models, fields, validation rules, and relationships. Download a complete Laravel project with auth, migrations, controllers, routes, and Blade CRUD views.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('generator.create') }}" class="ui-button-primary">Create New Project</a>
                                <a href="{{ route('generator.index') }}" class="ui-button-secondary">My Projects</a>
                            @else
                                <a href="{{ route('login') }}" class="ui-button-primary">Start Building</a>
                            @endauth
                        </div>
                    </div>

                    <div class="ui-card p-5">
                        <div class="grid gap-4">
                            <div class="ui-card-soft p-4">
                                <div class="text-sm font-semibold text-[#1E293B]">1. Application structure</div>
                                <p class="mt-1 text-sm text-[#64748B]">Create the application name and add the core models.</p>
                            </div>
                            <div class="ui-card-soft p-4">
                                <div class="text-sm font-semibold text-[#1E293B]">2. Fields and relations</div>
                                <p class="mt-1 text-sm text-[#64748B]">Choose data types, validation rules, and model relationships.</p>
                            </div>
                            <div class="ui-card-soft p-4">
                                <div class="text-sm font-semibold text-[#1E293B]">3. Downloadable Laravel project</div>
                                <p class="mt-1 text-sm text-[#64748B]">Get a ZIP with setup instructions and a ready-to-run app skeleton.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
