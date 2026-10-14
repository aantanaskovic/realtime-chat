<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class RemoveConversationMember
{
    public function handle(
        User $actor,
        Conversation $conversation,
        User $user,
    ): void {
        $actorRole = $conversation->users()
            ->whereKey($actor->id)
            ->first()
            ?->pivot
            ?->role;

        if ($actorRole !== ConversationRole::Owner) {
            throw new AuthorizationException;
        }

        if ($actor->is($user)) {
            throw new AuthorizationException;
        }

        $userRole = $conversation->users()
            ->whereKey($user->id)
            ->first()
            ?->pivot
            ?->role;

        if ($userRole === ConversationRole::Owner) {
            throw new AuthorizationException;
        }

        if ($userRole === null) {
            return;
        }

        $conversation->users()->detach($user);
    }
}
