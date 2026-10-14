<?php

use App\Events\Messages\MessageUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

it('broadcasts on the message conversation channel', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user);

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Updated message.',
    ]);

    $event = new MessageUpdated($message);

    expect($event->broadcastOn())->toEqual([
        new PrivateChannel('conversations.' . $conversation->id),
    ]);
});

it('broadcasts the updated message data', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user);

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Updated message.',
    ]);

    $event = new MessageUpdated($message);

    expect($event->broadcastWith())->toMatchArray([
        'message' => [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'body' => 'Updated message.',
        ],
    ]);
});

it('uses the MessageUpdated broadcast alias', function () {
    $message = Message::factory()->create();

    $event = new MessageUpdated($message);

    expect($event->broadcastAs())->toBe('MessageUpdated');
});
