<?php

use App\Events\Messages\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;

it('broadcasts on the private conversation channel', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $message = new Message([
        'body' => 'Hello from the test.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);

    $message->save();

    $event = new MessageSent($message);

    expect($event)
        ->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();

    expect($channels)
        ->toHaveCount(1)
        ->and($channels[0])
        ->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)
        ->toBe("private-conversations.{$conversation->id}");
});

it('broadcasts the expected message payload', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $message = new Message([
        'body' => 'Hello from the test.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);

    $message->save();

    $event = new MessageSent($message);

    expect($event->broadcastWith())
        ->toBe([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => 'Hello from the test.',
                'created_at' => $message->created_at?->toISOString(),
            ],
        ]);
});

it('broadcasts on the private channel of the conversation', function () {
    Event::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
    ]);

    $event = new MessageSent($message);

    expect($event->broadcastOn())->toEqual([
        new PrivateChannel('conversations.' . $conversation->id),
        ...$message->conversation->users->map(
            fn(User $user) => new PrivateChannel('App.Models.User.' . $user->id),
        ),
    ]);
});
