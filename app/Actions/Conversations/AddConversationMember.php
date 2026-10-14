<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationRole;
use App\Events\Conversations\ConversationAdded;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class AddConversationMember
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

        if ($conversation->users()->whereKey($user->id)->exists()) {
            return;
        }

        $conversation->users()->attach($user, [
            'role' => ConversationRole::Member,
        ]);

        event(new ConversationAdded($conversation, $user));
    }
}
