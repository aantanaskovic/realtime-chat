<?php

use App\Actions\Conversations\AddConversationMember;
use App\Actions\Conversations\CreateConversation;
use App\Actions\Conversations\DeleteConversation;
use App\Actions\Conversations\EditConversation;
use App\Actions\Conversations\RemoveConversationMember;
use App\Actions\Messages\EditMessage;
use App\Actions\Messages\SendMessage;
use App\Enums\ConversationRole;
use App\Events\Messages\UserTyping;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Validation\ConversationNameRules;
use App\Support\Validation\MessageBodyRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public Collection $conversations;

    public ?Conversation $conversation = null;

    public ?int $activeConversationId = null;

    public string $conversationName = '';

    public bool $editingConversationName = false;

    public string $editingConversationNameValue = '';

    public Collection $messages;

    public Collection $users;

    public array $memberStatuses = [];

    public string $body = '';

    public ?int $editingMessageId = null;

    public string $editingBody = '';

    public array $unreadConversationIds = [];

    public function mount(): void
    {
        $this->messages = new Collection();

        $this->users = User::query()->orderBy('name')->get();

        $this->loadConversations();

        $conversationIdFromQuery = request()->query('conversation');

        if ($conversationIdFromQuery !== null) {
            $this->conversation = Auth::user()->conversations()->find($conversationIdFromQuery);
        }

        if ($this->conversation) {
            $this->activeConversationId = $this->conversation->id;

            if (!Auth::user()->conversations()->whereKey($this->conversation->id)->exists()) {
                throw new AuthorizationException();
            }

            $this->markConversationAsRead($this->conversation);

            $this->loadUnreadConversations();

            $this->loadMessages();

            $this->loadMemberStatuses();
        }
    }

    public function createConversation(): void
    {
        $this->validate(ConversationNameRules::rules('conversationName'), ConversationNameRules::messages('conversationName'));

        $this->conversation = app(CreateConversation::class)->handle(user: Auth::user(), name: $this->conversationName);

        $this->loadConversations();

        $this->loadMessages();

        $this->loadMemberStatuses();

        $this->reset('conversationName');

        $this->dispatchActiveConversationChanged();
    }

    public function selectConversation(int $conversationId): void
    {
        $conversation = Auth::user()->conversations()->whereKey($conversationId)->first();

        if (!$conversation) {
            throw new AuthorizationException();
        }

        $this->conversation = $conversation;
        $this->activeConversationId = $conversation->id;

        $this->markConversationAsRead($this->conversation);

        $this->loadUnreadConversations();

        $this->loadMessages();

        $this->loadMemberStatuses();

        $this->reset('body');
        $this->reset('editingMessageId', 'editingBody');
        $this->resetValidation('editingBody');

        $this->dispatchActiveConversationChanged();
    }

    public function editConversation(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        if (!$this->isConversationOwner()) {
            throw new AuthorizationException();
        }

        $validated = $this->validate(ConversationNameRules::rules('editingConversationNameValue'), ConversationNameRules::messages('editingConversationNameValue'));

        app(EditConversation::class)->handle(actor: Auth::user(), conversation: $this->conversation, name: $validated['editingConversationNameValue']);

        $this->loadConversations();

        $this->conversationName = '';
        $this->editingConversationNameValue = $this->conversation->name;
        $this->editingConversationName = false;
    }

    public function startEditingConversationName(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        if (!$this->isConversationOwner()) {
            throw new AuthorizationException();
        }

        $this->editingConversationNameValue = $this->conversation->name;
        $this->editingConversationName = true;
    }

    public function cancelEditingConversationName(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        $this->editingConversationNameValue = $this->conversation->name;
        $this->editingConversationName = false;

        $this->resetValidation('editingConversationNameValue');
    }

    public function deleteConversation(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        app(DeleteConversation::class)->handle(actor: Auth::user(), conversation: $this->conversation);

        $this->conversation = null;
        $this->messages = new Collection();
        $this->memberStatuses = [];

        $this->loadConversations();

        $this->reset('body', 'editingMessageId', 'editingBody');

        $this->dispatchActiveConversationChanged();
    }

    public function handleConversationDeleted(array $event): void
    {
        $conversationId = (int) data_get($event, 'conversationId');

        if ($this->activeConversationId === $conversationId) {
            $this->conversation = null;
            $this->activeConversationId = null;

            $this->messages = new Collection();
            $this->memberStatuses = [];

            $this->reset('body', 'editingMessageId', 'editingBody');

            $this->dispatchActiveConversationChanged();
        }

        $this->loadConversations();
    }

    public function updatingConversation(): void
    {
        throw new AuthorizationException();
    }

    public function addMember(int $userId): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        if (!$this->conversation) {
            return;
        }

        $user = User::findOrFail($userId);

        app(AddConversationMember::class)->handle(actor: Auth::user(), conversation: $this->conversation, user: $user);

        $this->loadMemberStatuses();
    }

    public function removeMember(int $userId): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        if (!$this->conversation) {
            return;
        }

        $user = User::findOrFail($userId);

        app(RemoveConversationMember::class)->handle(actor: Auth::user(), conversation: $this->conversation, user: $user);

        $this->loadMemberStatuses();
    }

    public function sendMessage(): void
    {
        if (!$this->conversation) {
            return;
        }

        $this->validate(MessageBodyRules::rules(), MessageBodyRules::messages());

        app(SendMessage::class)->handle(user: Auth::user(), conversation: $this->conversation, body: $this->body);

        $this->loadMessages();

        $this->reset('body');
    }

    public function editMessage(int $messageId, string $body): void
    {
        if (!$this->conversation) {
            return;
        }

        $message = $this->conversation->messages()->findOrFail($messageId);

        app(EditMessage::class)->handle(user: Auth::user(), message: $message, body: $body);

        $this->loadMessages();

        $this->reset('editingMessageId', 'editingBody');
    }

    public function startEditingMessage(int $messageId): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        $message = $this->conversation->messages()->findOrFail($messageId);

        if (!$message->user()->is(Auth::user())) {
            throw new AuthorizationException();
        }

        $this->editingMessageId = $message->id;
        $this->editingBody = $message->body;
    }

    public function cancelEditingMessage(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        $this->reset('editingMessageId', 'editingBody');

        $this->resetValidation('editingBody');
    }

    public function saveEditedMessage(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        if ($this->editingMessageId === null) {
            return;
        }

        $this->validate(MessageBodyRules::rules('editingBody'), MessageBodyRules::messages('editingBody'));

        $this->editMessage($this->editingMessageId, $this->editingBody);
    }

    public function updatedEditingBody(): void
    {
        $this->resetValidation('editingBody');
    }

    public function isConversationOwner(): bool
    {
        if (!$this->conversation) {
            return false;
        }

        return $this->conversation->users()->whereKey(Auth::id())->wherePivot('role', ConversationRole::Owner)->exists();
    }

    private function loadConversations(): void
    {
        $this->conversations = Auth::user()->conversations()->oldest()->get();

        $this->loadUnreadConversations();
    }

    private function loadMessages(): void
    {
        $this->messages = $this->conversation->messages()->oldest()->get();
    }

    private function loadMemberStatuses(): void
    {
        if (!$this->conversation) {
            $this->memberStatuses = [];

            return;
        }

        $this->memberStatuses = $this->conversation
            ->users()
            ->get()
            ->mapWithKeys(
                fn(User $user) => [
                    $user->id => $user->pivot->role,
                ],
            )
            ->all();
    }

    public function refreshConversations(): void
    {
        $this->loadConversations();
        $this->loadMemberStatuses();
    }

    private function ensureConversationMembership(): bool
    {
        if (!$this->conversation || !Auth::user()->conversations()->whereKey($this->conversation->id)->exists()) {
            $this->conversation = null;
            $this->messages = new Collection();
            $this->memberStatuses = [];

            return false;
        }

        return true;
    }

    // BROADCASTING

    public function getListeners(): array
    {
        return [
            'echo-private:App.Models.User.' . Auth::id() . ',.ConversationAdded' => 'refreshConversations',
        ];
    }

    public function debugListeners(): array
    {
        return $this->getListeners();
    }

    public function refreshMessages(?array $event = null): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        $eventConversationId = data_get($event, 'message.conversation_id');

        if ($eventConversationId !== null && (int) $eventConversationId !== $this->conversation->id) {
            return;
        }

        if ($eventConversationId !== null) {
            $this->markConversationAsRead($this->conversation);
            $this->loadUnreadConversations();
        }

        $this->loadMessages();
    }

    public function startTyping(): void
    {
        if (!$this->ensureConversationMembership()) {
            return;
        }

        event(new UserTyping($this->conversation, Auth::user()));
    }

    private function dispatchActiveConversationChanged(): void
    {
        $this->dispatch('active-conversation-changed', conversationId: $this->conversation?->id);
    }

    private function markConversationAsRead(Conversation $conversation): void
    {
        $conversation->users()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);
    }

    private function loadUnreadConversations(): void
    {
        $userId = Auth::id();

        $this->unreadConversationIds = DB::table('messages')
            ->join('conversation_user', 'conversation_user.conversation_id', '=', 'messages.conversation_id')
            ->where('conversation_user.user_id', $userId)
            ->where('messages.user_id', '!=', $userId)
            ->where(function ($query) {
                $query->whereNull('conversation_user.last_read_at')->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
            })
            ->distinct()
            ->pluck('messages.conversation_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }
};
?>

<div class="min-h-[calc(100vh-4rem)] bg-slate-50/70">
    <div class="mx-auto flex max-w-[1600px] flex-col gap-5 p-4 sm:p-6 lg:flex-row lg:gap-6 lg:p-8">

        {{-- Conversations sidebar --}}
        <aside class="w-full shrink-0 lg:w-80">
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">
                            Workspace
                        </p>
                        <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900">
                            Messages
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $conversations->count() }} conversations
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 10h8M8 14h5m-8 6 3.5-3H18a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v14Z" />
                        </svg>
                    </div>
                </div>

                {{-- Create conversation --}}
                <div class="border-b border-slate-100 bg-slate-50/70 p-4">
                    <form wire:submit="createConversation" class="space-y-3">
                        <label for="newConversationName" class="block text-sm font-semibold text-slate-800">
                            Start a conversation
                        </label>

                        <div class="flex gap-2">
                            <input id="newConversationName" type="text" wire:model="conversationName" maxlength="255"
                                placeholder="Conversation name..."
                                class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10">

                            <button type="submit" aria-label="Create conversation" title="Create conversation"
                                wire:loading.attr="disabled" wire:target="createConversation"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-wait disabled:opacity-60">
                                <svg wire:loading.remove wire:target="createConversation" class="h-5 w-5"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" />
                                </svg>
                                <svg wire:loading wire:target="createConversation" class="h-5 w-5 animate-spin"
                                    viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor"
                                        stroke-width="3" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z" />
                                </svg>
                            </button>
                        </div>

                        @error('conversationName')
                            <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>

                {{-- Conversation list --}}
                <div class="p-3">
                    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Your conversations
                    </p>

                    <nav class="space-y-1" aria-label="Conversations">
                        @forelse ($conversations as $conversationItem)
                            <button type="button" wire:key="conversation-{{ $conversationItem->id }}"
                                wire:click="selectConversation({{ $conversationItem->id }})"
                                wire:loading.attr="disabled" wire:target="selectConversation"
                                @class([
                                    'group relative flex w-full items-center gap-3 rounded-xl p-3 pr-4 text-left transition duration-150 focus:outline-none focus:ring-4 focus:ring-indigo-500/10',
                                    'bg-indigo-50 ring-1 ring-inset ring-indigo-100' =>
                                        $conversation && $conversation->id === $conversationItem->id,
                                    'hover:bg-slate-50' =>
                                        !$conversation || $conversation->id !== $conversationItem->id,
                                ])>
                                <span @class([
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold transition',
                                    'bg-indigo-600 text-white shadow-sm' =>
                                        $conversation && $conversation->id === $conversationItem->id,
                                    'bg-slate-100 text-slate-600 group-hover:bg-indigo-100 group-hover:text-indigo-700' =>
                                        !$conversation || $conversation->id !== $conversationItem->id,
                                ])>
                                    {{ mb_strtoupper(mb_substr($conversationItem->name, 0, 1)) }}
                                </span>

                                <span class="flex min-w-0 flex-1 flex-col">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span @class([
                                            'min-w-0 flex-1 truncate text-sm font-semibold',
                                            'text-indigo-950' =>
                                                $conversation && $conversation->id === $conversationItem->id,
                                            'text-slate-800' =>
                                                !$conversation || $conversation->id !== $conversationItem->id,
                                        ])>
                                            {{ $conversationItem->name }}
                                        </span>
                                    </span>

                                    <span class="mt-1 flex items-center justify-between gap-2">
                                        <span class="text-xs text-slate-400">
                                            Open conversation
                                        </span>
                                    </span>
                                </span>

                                <span class="flex shrink-0 items-center gap-2">
                                    @if (in_array($conversationItem->id, $unreadConversationIds, true))
                                        <span aria-label="Unread messages" title="Unread messages"
                                            class="inline-flex items-center gap-1 rounded-full bg-rose-600 px-2 py-1 text-[10px] font-bold leading-none text-white shadow-sm">
                                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                            New
                                        </span>
                                    @endif

                                    @if ($conversation && $conversation->id === $conversationItem->id)
                                        <svg class="h-4 w-4 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4 shrink-0 text-slate-300 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                        </svg>
                                    @endif
                                </span>

                            </button>
                        @empty
                            <div class="px-4 py-10 text-center">
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 10h8M8 14h5m-8 6 3.5-3H18a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v14Z" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-slate-700">No conversations yet</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Create your first conversation above
                                    to
                                    get started.</p>
                            </div>
                        @endforelse
                    </nav>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                </div>
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-4 ring-emerald-50" title="Signed in"></span>
            </div>
        </aside>

        {{-- Main chat area --}}
        <main class="flex min-w-0 flex-1 flex-col gap-5">
            @if ($conversation)
                {{-- Conversation header --}}
                <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-lg font-bold text-white shadow-sm shadow-indigo-200">
                                {{ mb_strtoupper(mb_substr($conversation->name, 0, 1)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                @if ($editingConversationName)
                                    <form wire:submit="editConversation" class="space-y-3">
                                        <div>
                                            <label for="editingConversationNameValue"
                                                class="mb-1 block text-sm font-semibold text-slate-700">
                                                Conversation name
                                            </label>
                                            <input id="editingConversationNameValue" type="text"
                                                wire:model="editingConversationNameValue" maxlength="255" autofocus
                                                class="block w-full max-w-md rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10">
                                            @error('editingConversationNameValue')
                                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="submit"
                                                class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20">
                                                Save changes
                                            </button>
                                            <button type="button" wire:click="cancelEditingConversationName"
                                                class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/10">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <h1
                                        class="break-words text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        {{ $conversation->name }}
                                    </h1>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Active conversation
                                        </span>
                                        <span class="text-xs text-slate-400">
                                            {{ $messages->count() }}
                                            {{ $messages->count() === 1 ? 'message' : 'messages' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if (!$editingConversationName)
                            <div class="flex items-center gap-2">
                                @if ($this->isConversationOwner())
                                    <button type="button" wire:click="startEditingConversationName"
                                        title="Edit conversation name"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/10">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 2.651 2.651M8 16l8.5-8.5a2.121 2.121 0 0 1 3 3L11 19H8v-3Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h6" />
                                        </svg>
                                        <span class="hidden sm:inline">Edit name</span>
                                    </button>

                                    <button type="button" wire:click="deleteConversation"
                                        wire:confirm="Are you sure you want to permanently delete this conversation? This action cannot be undone."
                                        title="Delete conversation"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-red-100 text-red-600 transition hover:border-red-200 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-500/10">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 10v6m4-6v6" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Members panel --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">People</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $users->count() }} {{ $users->count() === 1 ? 'user' : 'users' }} available
                            </p>
                        </div>

                        @if ($this->isConversationOwner())
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m9 12 2 2 4-4m5.5-1A10 10 0 1 1 12 2a10 10 0 0 1 8.5 7Z" />
                                </svg>
                                Owner controls
                            </span>
                        @endif
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <div wire:key="user-{{ $user->id }}"
                                class="flex flex-wrap items-center gap-3 px-5 py-3.5 transition hover:bg-slate-50/70 sm:flex-nowrap">
                                <div class="relative shrink-0">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    @if (isset($memberStatuses[$user->id]))
                                        <span
                                            class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500"
                                            title="Conversation member"></span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800">
                                        {{ $user->name }}
                                        @if ($user->id === auth()->id())
                                            <span class="ml-1 text-xs font-normal text-slate-400">(You)</span>
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        @if (isset($memberStatuses[$user->id]))
                                            {{ $memberStatuses[$user->id] }}
                                        @else
                                            Not a member
                                        @endif
                                    </p>
                                </div>

                                @if ($this->isConversationOwner() && !isset($memberStatuses[$user->id]))
                                    <button type="button" wire:click="addMember({{ $user->id }})"
                                        wire:loading.attr="disabled" wire:target="addMember({{ $user->id }})"
                                        class="rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="addMember({{ $user->id }})">Add
                                            member</span>
                                        <span wire:loading wire:target="addMember({{ $user->id }})">Adding…</span>
                                    </button>
                                @endif

                                @if ($this->isConversationOwner() && isset($memberStatuses[$user->id]) && $user->id !== auth()->id())
                                    <button type="button" wire:click="removeMember({{ $user->id }})"
                                        wire:confirm="Remove {{ $user->name }} from this conversation?"
                                        wire:loading.attr="disabled" wire:target="removeMember({{ $user->id }})"
                                        class="rounded-lg border border-red-100 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:border-red-200 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-500/10 disabled:opacity-50">
                                        Remove
                                    </button>
                                @endif
                            </div>
                        @empty
                            <p class="px-5 py-8 text-center text-sm text-slate-500">
                                No users to display.
                            </p>
                        @endforelse
                    </div>
                </section>

                {{-- Messages --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Conversation</h2>
                            <p class="mt-1 text-xs text-slate-500">Your messages appear on the right</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                            {{ $messages->count() }}
                        </span>
                    </div>

                    <div class="space-y-5 p-4 sm:p-6">
                        @forelse ($messages as $message)
                            @php
                                $isOwnMessage = $message->user_id === auth()->id();
                            @endphp

                            <article wire:key="message-{{ $message->id }}" @class(['flex gap-3', 'flex-row-reverse' => $isOwnMessage])>
                                <div @class([
                                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                    'bg-indigo-100 text-indigo-700' => $isOwnMessage,
                                    'bg-slate-100 text-slate-600' => !$isOwnMessage,
                                ])>
                                    {{ mb_strtoupper(mb_substr($message->user->name, 0, 1)) }}
                                </div>

                                <div @class([
                                    'min-w-0 max-w-[85%] sm:max-w-[75%]',
                                    'items-end' => $isOwnMessage,
                                ])>
                                    <div @class([
                                        'mb-1.5 flex flex-wrap items-center gap-2',
                                        'justify-end' => $isOwnMessage,
                                    ])>
                                        <span class="text-xs font-semibold text-slate-700">
                                            {{ $isOwnMessage ? 'You' : $message->user->name }}
                                        </span>
                                        <span class="text-[11px] text-slate-400">
                                            {{ $message->created_at->format('M j, H:i') }}
                                        </span>
                                    </div>

                                    @if ($editingMessageId === $message->id)
                                        <form wire:submit="saveEditedMessage"
                                            class="w-full min-w-[240px] space-y-3 rounded-2xl border border-indigo-100 bg-indigo-50/60 p-3">
                                            <label for="editingBody"
                                                class="block text-xs font-semibold text-slate-700">
                                                Edit your message
                                            </label>
                                            <textarea id="editingBody" wire:model="editingBody" wire:keydown.throttle.1000ms="startTyping" rows="3"
                                                maxlength="5000"
                                                class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10"></textarea>
                                            @error('editingBody')
                                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                                            @enderror
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button type="button" wire:click="cancelEditingMessage"
                                                    class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-white">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                                    Save edit
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div @class([
                                            'rounded-2xl px-4 py-3 shadow-sm',
                                            'rounded-tr-md bg-indigo-600 text-white' => $isOwnMessage,
                                            'rounded-tl-md bg-slate-100 text-slate-800' => !$isOwnMessage,
                                        ])>
                                            <p class="whitespace-pre-wrap break-words text-sm leading-6">
                                                {{ $message->body }}</p>
                                        </div>

                                        @if ($isOwnMessage)
                                            <div class="mt-1.5 flex justify-end">
                                                <button type="button"
                                                    wire:click="startEditingMessage({{ $message->id }})"
                                                    class="rounded-md px-2 py-1 text-xs font-semibold text-slate-400 transition hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                                                    Edit message
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="flex flex-col items-center px-4 py-12 text-center">
                                <div
                                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.7">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8 10h8M8 14h5m-8 6 3.5-3H18a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v14Z" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-sm font-bold text-slate-800">A fresh start</h3>
                                <p class="mt-1 max-w-xs text-sm leading-6 text-slate-500">
                                    There are no messages here yet. Send the first message and get the conversation
                                    going.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </section>



                <div class="min-h-9">
                    <div id="typing-indicator"
                        class="hidden w-fit items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 shadow-sm"
                        aria-live="polite"></div>
                </div>



                {{-- Message composer --}}
                <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5">
                    <form wire:submit="sendMessage" class="space-y-3">
                        <label for="body" class="block text-sm font-semibold text-slate-800">
                            Write a message
                        </label>

                        <textarea id="body" wire:model="body" wire:keydown.throttle.1000ms="startTyping" rows="3"
                            maxlength="5000" placeholder="Message {{ $conversation->name }}..."
                            class="block w-full resize-y rounded-xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm leading-6 text-slate-800 placeholder:text-slate-400 outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10"></textarea>

                        @error('body')
                            <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-400">
                                <span class="font-semibold">{{ mb_strlen($body) }}</span>/5000 characters
                            </p>

                            <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-200 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500/20 disabled:cursor-wait disabled:opacity-60">
                                <span wire:loading.remove wire:target="sendMessage">Send message</span>
                                <span wire:loading wire:target="sendMessage">Sending…</span>
                                <svg wire:loading.remove wire:target="sendMessage" class="h-4 w-4"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section
                    class="flex min-h-[560px] flex-1 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-8 text-center shadow-sm">
                    <div class="max-w-md">
                        <div
                            class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-indigo-50 to-violet-100 text-indigo-600">
                            <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 10h8M8 14h5m-8 6 3.5-3H18a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v14Z" />
                            </svg>
                        </div>
                        <p class="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">
                            Your workspace
                        </p>
                        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                            Good conversations start here.
                        </h1>
                        <p class="mx-auto mt-3 max-w-sm text-sm leading-6 text-slate-500">
                            Select a conversation from the sidebar or create a new one to start sharing messages with
                            your team.
                        </p>
                        <div
                            class="mt-6 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-medium text-slate-500">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            Select a conversation to continue
                        </div>
                    </div>
                </section>
            @endif
        </main>
    </div>
</div>

@script
    <script>
        window.initializeChatRealtime(
            $wire,
            @js($conversation?->id),
            @js(Auth::id())
        );
    </script>
@endscript
