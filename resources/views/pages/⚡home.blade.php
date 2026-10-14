<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.guest')] class extends Component {};
?>

<main class="overflow-hidden">
    <section class="relative isolate">
        <div class="absolute inset-x-0 top-0 -z-10 h-[42rem] bg-gradient-to-b from-indigo-50/80 via-white to-white">
        </div>
        <div
            class="mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-16 sm:px-8 sm:pb-28 sm:pt-24 lg:grid-cols-[1.02fr_.98fr] lg:gap-16 lg:pt-28">
            <div>
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white px-3.5 py-1.5 text-sm font-medium text-indigo-700 shadow-sm">
                    <span class="relative flex size-2"><span
                            class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60"></span><span
                            class="relative inline-flex size-2 rounded-full bg-emerald-500"></span></span>
                    Conversations, connected in real time
                </div>
                <h1
                    class="mt-7 max-w-2xl text-5xl leading-[1.08] font-bold tracking-tight text-slate-950 sm:text-6xl lg:text-[4.25rem]">
                    Good conversations move things <span class="text-indigo-600">forward.</span></h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">A focused space for your team to talk, share
                    updates, and stay in sync — with messages that arrive as they happen.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700">Start
                        a conversation <span aria-hidden="true">→</span></a>
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">I
                        already have an account</a>
                </div>
                <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500">
                    <span class="inline-flex items-center gap-2"><span class="text-emerald-600">✓</span> Live message
                        updates</span>
                    <span class="inline-flex items-center gap-2"><span class="text-emerald-600">✓</span> Private
                        conversations</span>
                    <span class="inline-flex items-center gap-2"><span class="text-emerald-600">✓</span> Unread
                        indicators</span>
                </div>
            </div>
            <div class="relative mx-auto w-full max-w-xl">
                <div
                    class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-gradient-to-br from-indigo-200/60 via-violet-100/50 to-sky-100/60 blur-2xl">
                </div>
                <div
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path
                                        d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900">Project Team</p>
                                <p class="text-xs text-slate-500">4 members · Conversation</p>
                            </div>
                        </div>
                        <span class="flex items-center gap-1.5 text-xs font-medium text-emerald-700"><span
                                class="size-2 rounded-full bg-emerald-500"></span> Live</span>
                    </div>
                    <div class="space-y-5 bg-slate-50/70 p-5 sm:p-6">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-800">
                                AM</div>
                            <div class="max-w-[85%]">
                                <p class="mb-1 text-xs font-semibold text-slate-600">Alex Morgan <span
                                        class="ml-1 font-normal text-slate-400">10:42</span></p>
                                <div
                                    class="rounded-2xl rounded-tl-sm border border-slate-200 bg-white px-4 py-3 text-sm leading-6 text-slate-700 shadow-sm">
                                    The latest update is ready. Can everyone take a quick look?</div>
                            </div>
                        </div>
                        <div class="flex items-start justify-end gap-3">
                            <div class="max-w-[85%]">
                                <p class="mb-1 text-right text-xs font-semibold text-slate-600">You <span
                                        class="ml-1 font-normal text-slate-400">10:43</span></p>
                                <div
                                    class="rounded-2xl rounded-tr-sm bg-indigo-600 px-4 py-3 text-sm leading-6 text-white shadow-sm">
                                    Looks great to me! The live updates make this so much easier.</div>
                                <p class="mt-1.5 text-right text-xs text-indigo-600">✓✓ Sent</p>
                            </div>
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                YO</div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800">
                                JS</div>
                            <div>
                                <p class="mb-1 text-xs font-semibold text-slate-600">Jamie Smith <span
                                        class="ml-1 font-normal text-slate-400">now</span></p>
                                <div
                                    class="rounded-2xl rounded-tl-sm border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    Agreed — shipping it today.</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pl-12"><span
                                class="flex gap-1 rounded-full border border-slate-200 bg-white px-3 py-2 shadow-sm"><span
                                    class="size-1.5 animate-bounce rounded-full bg-slate-400"></span><span
                                    class="size-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:120ms]"></span><span
                                    class="size-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:240ms]"></span></span><span
                                class="text-xs text-slate-500">Someone is typing…</span></div>
                    </div>
                    <div class="border-t border-slate-200 bg-white p-4">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="flex-1 text-sm text-slate-400">Write a message…</span><span
                                class="flex size-8 items-center justify-center rounded-lg bg-indigo-600 text-white">↑</span>
                        </div>
                    </div>
                </div>
                <div
                    class="absolute -right-3 top-20 hidden rounded-xl border border-slate-100 bg-white p-3 shadow-lg sm:block">
                    <div class="flex items-center gap-2"><span
                            class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">✓</span>
                        <div>
                            <p class="text-xs font-semibold text-slate-800">Message received</p>
                            <p class="text-[11px] text-slate-500">Updated instantly</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="features" class="border-y border-slate-200/80 bg-slate-50 py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold tracking-widest text-indigo-600 uppercase">Made for momentum</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Everything you need to
                    stay in sync</h2>
                <p class="mt-4 text-lg leading-8 text-slate-600">Less switching between tools. More clarity, context,
                    and progress in every conversation.</p>
            </div>
            <div class="mt-12 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-700">
                        ↯</div>
                    <h3 class="mt-5 text-base font-bold text-slate-900">Real-time messaging</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Messages appear as they happen, so your team can
                        keep the conversation moving.</p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-xl text-emerald-700">
                        •••</div>
                    <h3 class="mt-5 text-base font-bold text-slate-900">Typing indicators</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">See when someone is composing a reply and make
                        chats feel more natural.</p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-xl text-amber-700">
                        ●</div>
                    <h3 class="mt-5 text-base font-bold text-slate-900">Unread messages</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Spot conversations with new messages and pick up
                        right where you left off.</p>
                </article>
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                    <div
                        class="flex size-11 items-center justify-center rounded-xl bg-violet-50 text-xl text-violet-700">
                        ♧</div>
                    <h3 class="mt-5 text-base font-bold text-slate-900">Private spaces</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Keep conversations organized with membership and
                        role-based controls.</p>
                </article>
            </div>
        </div>
    </section>
    <section class="px-5 py-20 sm:px-8 sm:py-24">
        <div
            class="mx-auto flex max-w-5xl flex-col items-center rounded-3xl bg-slate-950 px-6 py-12 text-center shadow-xl sm:px-12 sm:py-16">
            <span
                class="rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold tracking-wide text-indigo-200">YOUR
                TEAM, IN SYNC</span>
            <h2 class="mt-5 max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl">Make room for better
                conversations.</h2>
            <p class="mt-4 max-w-xl leading-7 text-slate-300">Create your account and bring your conversations into one
                focused space.</p><a href="{{ route('register') }}"
                class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-slate-950 transition hover:bg-indigo-50">Get
                started for free <span aria-hidden="true">→</span></a>
        </div>
    </section>
</main>
