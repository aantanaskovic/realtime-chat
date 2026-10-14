<?php

use App\Events\Messages\UserTyping;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;

it('broadcasts when a conversation member starts typing', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);
    $conversation->users()->attach($otherUser);

    Event::fake();

    event(new UserTyping($conversation, $user));

    Event::assertDispatched(UserTyping::class, function (UserTyping $event) use ($conversation, $user) {
        return $event->conversation->is($conversation)
            && $event->user->is($user);
    });
});

it('broadcasts typing updates on the conversation private channel', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $event = new UserTyping($conversation, $user);

    expect($event->broadcastOn())
        ->toEqual([
            new PrivateChannel('conversations.' . $conversation->id),
        ]);
});

it('includes the conversation and typing user in the broadcast payload', function () {
    $user = User::factory()->create(['name' => 'Alex']);
    $conversation = Conversation::factory()->create();

    $event = new UserTyping($conversation, $user);

    expect($event->broadcastWith())->toBe([
        'conversationId' => $conversation->id,
        'user' => [
            'id' => $user->id,
            'name' => 'Alex',
        ],
    ]);
});

it('implements the ShouldBroadcast contract', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $event = new UserTyping($conversation, $user);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});
