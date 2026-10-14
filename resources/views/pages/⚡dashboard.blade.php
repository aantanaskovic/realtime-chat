<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function with(): array
    {
        $conversations = Auth::user()->conversations()->withCount('messages')->withMax('messages', 'created_at')->orderByDesc('messages_max_created_at')->orderByDesc('conversations.updated_at')->limit(4)->get();

        return [
            'conversations' => $conversations,
            'conversationCount' => Auth::user()->conversations()->count(),
            'messageCount' => Auth::user()->conversations()->withCount('messages')->get()->sum('messages_count'),
        ];
    }
};
?>

<div class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
    <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-indigo-600">YOUR WORKSPACE</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Welcome back,
                {{ auth()->user()->name }}.</h1>
            <p class="mt-3 max-w-2xl text-slate-600">Your conversations, all in one place. Pick up where you left off or
                start something new.</p>
        </div>
        <a href="{{ route('chat') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Open
            chat <span aria-hidden="true">→</span></a>
    </div>

    <div class="mt-9 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Your conversations</p><span
                    class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">▤</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-950">{{ $conversationCount }}</p>
            <p class="mt-1 text-sm text-slate-500">Spaces you are a member of</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Messages in your spaces</p><span
                    class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">↗</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-slate-950">{{ $messageCount }}</p>
            <p class="mt-1 text-sm text-slate-500">Across conversations you can access</p>
        </div>
    </div>

    <section class="mt-10 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <h2 class="font-bold text-slate-950">Your conversations</h2>
                <p class="mt-1 text-sm text-slate-500">Jump back into a recent discussion.</p>
            </div><a href="{{ route('chat') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View
                all <span aria-hidden="true">→</span></a>
        </div>
        @forelse ($conversations as $conversation)
            <a href="{{ route('chat', ['conversation' => $conversation->id]) }}"
                class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4 transition last:border-b-0 hover:bg-slate-50">
                <div class="flex min-w-0 items-center gap-3"><span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($conversation->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-slate-900">{{ $conversation->name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $conversation->messages_count }}
                            {{ \Illuminate\Support\Str::plural('message', $conversation->messages_count) }}</p>
                    </div>
                </div>
                <span class="shrink-0 text-sm font-medium text-slate-400">Open <span aria-hidden="true">→</span></span>
            </a>
        @empty
            <div class="px-6 py-12 text-center"><span
                    class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-xl text-indigo-700">✦</span>
                <h3 class="mt-4 font-semibold text-slate-900">Your first conversation starts here</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Create a conversation, invite your
                    teammates, and keep every update in one place.</p><a href="{{ route('chat') }}"
                    class="mt-5 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create
                    a conversation <span aria-hidden="true">→</span></a>
            </div>
        @endforelse
    </section>
    <p class="mt-6 text-center text-xs text-slate-400">Messages are updated in real time while you chat.</p>
</div>
