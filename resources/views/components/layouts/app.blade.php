<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <title>{{ $title ?? config('app.name', 'Relay') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (request()->routeIs('chat'))
        @vite('resources/js/chat.js')
    @endif
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8" aria-label="Main navigation">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span
                    class="flex size-9 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-sm">R</span>
                <span class="text-lg font-bold tracking-tight text-slate-950">Relay<span
                        class="text-indigo-600">.</span></span>
            </a>
            <div class="flex items-center gap-2 sm:gap-5">
                <a href="{{ route('dashboard') }}" @class([
                    'rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-slate-100',
                    'bg-indigo-50 text-indigo-700' => request()->routeIs('dashboard'),
                    'text-slate-600' => !request()->routeIs('dashboard'),
                ])>Dashboard</a>
                <a href="{{ route('chat') }}" @class([
                    'rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-slate-100',
                    'bg-indigo-50 text-indigo-700' => request()->routeIs('chat'),
                    'text-slate-600' => !request()->routeIs('chat'),
                ])>Chat</a>
                <span class="hidden h-7 w-px bg-slate-200 sm:block"></span>
                <span class="hidden text-sm font-medium text-slate-600 sm:block">{{ auth()->user()?->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">Log
                        out</button>
                </form>
            </div>
        </nav>
    </header>
    <main>{{ $slot }}</main>
    @livewireScripts
</body>

</html>
