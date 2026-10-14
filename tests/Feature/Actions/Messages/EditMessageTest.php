<?php

use App\Actions\Messages\EditMessage;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('allows the author to edit their message', function () {
    Event::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $updatedMessage = app(EditMessage::class)->handle(
        user: $user,
        message: $message,
        body: 'Updated message',
    );

    expect($updatedMessage->body)->toBe('Updated message');

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'body' => 'Updated message',
    ]);
});

it('does not allow a user to edit another users message', function () {
    $author = User::factory()->create();
    $anotherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($author);
    $conversation->users()->attach($anotherUser);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($author);
    $message->save();

    expect(fn() => app(EditMessage::class)->handle(
        user: $anotherUser,
        message: $message,
        body: 'Modified by another user',
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'body' => 'Original message',
    ]);
});

it('does not allow a user to edit a message after being removed from the conversation', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner);
    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $conversation->users()->detach($user);

    expect(fn() => app(EditMessage::class)->handle(
        user: $user,
        message: $message,
        body: 'Unauthorized edit',
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'body' => 'Original message',
    ]);
});

it('requires a message body', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    expect(fn() => app(EditMessage::class)->handle(
        user: $user,
        message: $message,
        body: '',
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'body' => 'Original message',
    ]);
});

it('does not allow a message body longer than 5000 characters', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    expect(fn() => app(EditMessage::class)->handle(
        user: $user,
        message: $message,
        body: str_repeat('a', 5001),
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'body' => 'Original message',
    ]);
});
