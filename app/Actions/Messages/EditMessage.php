<?php

namespace App\Actions\Messages;

use App\Events\Messages\MessageUpdated;
use App\Models\Message;
use App\Models\User;
use App\Support\Validation\MessageBodyRules;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;

class EditMessage
{
    public function handle(
        User $user,
        Message $message,
        string $body,
    ): Message {
        if (! $message->user()->is($user)) {
            throw new AuthorizationException;
        }

        if (! $message->conversation->users()->whereKey($user->id)->exists()) {
            throw new AuthorizationException();
        }

        Validator::make(
            ['body' => $body],
            MessageBodyRules::rules(),
            MessageBodyRules::messages(),
        )->validate();

        $message->update([
            'body' => $body,
        ]);

        event(new MessageUpdated($message));

        return $message;
    }
}
