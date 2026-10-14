<?php

namespace App\Actions\Messages;

use App\Events\Messages\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\Validation\MessageBodyRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;

class SendMessage
{
    public function handle(
        User $user,
        Conversation $conversation,
        string $body,
    ): Message {
        Validator::make(
            ['body' => $body],
            MessageBodyRules::rules(),
            MessageBodyRules::messages(),
        )->validate();

        if (! $conversation->users()->whereKey($user->id)->exists()) {
            throw new AuthorizationException;
        }

        $message = new Message([
            'body' => $body,
        ]);

        $message->conversation()->associate($conversation);
        $message->user()->associate($user);

        $message->save();

        event(new MessageSent($message));

        return $message;
    }
}
