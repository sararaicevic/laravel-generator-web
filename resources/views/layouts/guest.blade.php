<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="app-shell flex min-h-screen items-center justify-center px-4 py-10">
            <div class="grid w-full max-w-5xl gap-8 lg:grid-cols-[1fr_420px] lg:items-center">
                <div class="hidden lg:block">
                    <a href="/" class="inline-flex items-center gap-3 text-[#1E293B]">
                        <x-application-logo class="h-12 w-12" />
                        <span class="text-lg font-semibold">Laravel DSL</span>
                    </a>

                    <div class="mt-14 max-w-xl">
                        <p class="ui-pill">Laravel DSL</p>
                        <h1 class="mt-6 text-5xl font-bold leading-tight text-[#1E293B]">
                            Sign in and continue building applications.
                        </h1>
                        <p class="mt-5 max-w-lg text-base leading-7 text-[#64748B]">
                            Manage projects, define models, and download generated Laravel code from one clean workspace.
                        </p>
                    </div>
                </div>

                <div class="w-full">
                    <div class="mb-7 flex justify-center lg:hidden">
                        <a href="/" class="inline-flex items-center gap-3 text-[#1E293B]">
                            <x-application-logo class="h-11 w-11" />
                            <span class="text-lg font-semibold">Laravel DSL</span>
                        </a>
                    </div>

                    <div class="glass-panel w-full rounded-lg p-6 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
