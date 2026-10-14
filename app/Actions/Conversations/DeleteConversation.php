<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationRole;
use App\Events\Conversations\ConversationDeleted;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeleteConversation
{
    public function handle(User $actor, Conversation $conversation): void
    {
        $actorRole = $conversation->users()
            ->whereKey($actor->id)
            ->first()
            ?->pivot
            ?->role;

        if ($actorRole !== ConversationRole::Owner) {
            throw new AuthorizationException();
        }

        $memberIds = $conversation->users()
            ->pluck('users.id')
            ->all();

        DB::transaction(function () use ($conversation): void {
            $conversation->delete();
        });

        event(new ConversationDeleted($conversation->id, $memberIds));
    }
}
