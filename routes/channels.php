<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversations.{conversationId}', function (
    User $user,
    int $conversationId,
): bool {
    return Conversation::query()
        ->whereKey($conversationId)
        ->whereHas('users', fn($query) => $query->whereKey($user->id))
        ->exists();
});
