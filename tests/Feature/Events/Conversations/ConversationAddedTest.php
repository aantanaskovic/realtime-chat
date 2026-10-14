<?php

use App\Events\Conversations\ConversationAdded;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

it('broadcasts on the private channel of the added user', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $event = new ConversationAdded($conversation, $user);

    expect($event->broadcastOn())->toEqual([
        new PrivateChannel('App.Models.User.' . $user->id),
    ]);
});

it('broadcasts the conversation details', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Shared conversation',
    ]);

    $event = new ConversationAdded($conversation, $user);

    expect($event->broadcastWith())->toBe([
        'conversation' => [
            'id' => $conversation->id,
            'name' => 'Shared conversation',
        ],
    ]);
});

it('uses the ConversationAdded broadcast event name', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $event = new ConversationAdded($conversation, $user);

    expect($event->broadcastAs())->toBe('ConversationAdded');
});
