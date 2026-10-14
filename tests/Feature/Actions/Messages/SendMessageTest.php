<?php

use App\Actions\Messages\SendMessage;
use App\Events\Messages\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('does not send a message when user is not a conversation member', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    expect(fn() => app(SendMessage::class)->handle(
        user: $user,
        conversation: $conversation,
        body: 'Hello from the test.',
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('messages', 0);
});

it('does not send an empty message', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    expect(fn() => app(SendMessage::class)->handle(
        user: $user,
        conversation: $conversation,
        body: '',
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('messages', 0);
});

it('does not send a message longer than 5000 characters', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    expect(fn() => app(SendMessage::class)->handle(
        user: $user,
        conversation: $conversation,
        body: str_repeat('a', 5001),
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('messages', 0);
});

it('does not send a message containing only whitespace', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    expect(fn() => app(SendMessage::class)->handle(
        user: $user,
        conversation: $conversation,
        body: '     ',
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('messages', 0);
});


// BROADCASTING

it('broadcasts the message after successfully sending it', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = app(SendMessage::class)->handle(
        user: $user,
        conversation: $conversation,
        body: 'Hello from the test.',
    );

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Hello from the test.',
    ]);

    Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
        return $event->message->is($message);
    });
});

it('does not broadcast a message when sending fails', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    expect(fn() => app(SendMessage::class)->handle(
        user: $user,
        conversation: $conversation,
        body: '',
    ))->toThrow(ValidationException::class);

    Event::assertNotDispatched(MessageSent::class);

    $this->assertDatabaseCount('messages', 0);
});
