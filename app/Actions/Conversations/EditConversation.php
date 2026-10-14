<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Validation\ConversationNameRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;

class EditConversation
{
    public function handle(
        User $actor,
        Conversation $conversation,
        string $name,
    ): Conversation {
        $actorRole = $conversation->users()
            ->whereKey($actor->id)
            ->first()
            ?->pivot
            ?->role;

        if ($actorRole !== ConversationRole::Owner) {
            throw new AuthorizationException();
        }

        $validated = Validator::make(
            ['name' => $name],
            ConversationNameRules::rules(),
            ConversationNameRules::messages(),
        )->validate();

        $conversation->update([
            'name' => trim($validated['name']),
        ]);

        return $conversation;
    }
}
