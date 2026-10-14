<?php

namespace App\Actions\Conversations;

use App\Enums\ConversationRole;
use App\Events\Conversations\ConversationAdded;
use App\Models\Conversation;
use App\Models\User;
use App\Support\Validation\ConversationNameRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateConversation
{

    public function handle(
        User $user,
        string $name,
    ): Conversation {
        Validator::make(
            ['name' => $name],
            ConversationNameRules::rules(),
            ConversationNameRules::messages(),
        )->validate();

        $conversation = DB::transaction(function () use ($user, $name): Conversation {
            $conversation = Conversation::create([
                'name' => $name,
            ]);

            $conversation->users()->attach($user, [
                'role' => ConversationRole::Owner,
            ]);

            return $conversation;
        });

        event(new ConversationAdded($conversation, $user));

        return $conversation;
    }
}
