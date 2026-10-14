<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>{{ $title ?? config('app.name', 'Relay') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-white text-slate-900 antialiased">
    <header class="relative z-20 border-b border-slate-200/70 bg-white/90">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8" aria-label="Main navigation">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span
                    class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-sm">R</span>
                <span class="text-lg font-bold tracking-tight text-slate-950">Relay<span
                        class="text-indigo-600">.</span></span>
            </a>
            <div class="flex items-center gap-3 sm:gap-6">
                <a href="{{ route('home') }}#features"
                    class="hidden text-sm font-medium text-slate-600 transition hover:text-indigo-600 sm:inline">Features</a>
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="text-sm font-semibold text-slate-600 hover:text-indigo-600">Dashboard</a>
                    <a href="{{ route('chat') }}"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Open
                        chat</a>
                @else
                    <a href="{{ route('login') }}"
                        class="text-sm font-semibold text-slate-600 transition hover:text-indigo-600">Sign in</a>
                    <a href="{{ route('register') }}"
                        class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Get
                        started <span aria-hidden="true">→</span></a>
                @endauth
            </div>
        </nav>
    </header>
    {{ $slot }}
    <footer class="border-t border-slate-200 bg-slate-50">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <a href="{{ route('home') }}" class="font-bold tracking-tight text-slate-800">Relay<span
                    class="text-indigo-600">.</span></a>
            <p>Thoughtful conversations. In real time.</p>
            <p>Built with Laravel, Livewire and Reverb.</p>
        </div>
    </footer>
    @livewireScripts
</body>

</html>
