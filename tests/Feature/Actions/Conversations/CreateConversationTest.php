<?php

use App\Actions\Conversations\CreateConversation;
use App\Enums\ConversationRole;
use App\Events\Conversations\ConversationAdded;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('creates a conversation', function () {
    Event::fake();

    $user = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        user: $user,
        name: 'Project Alpha',
    );

    expect($conversation->name)->toBe('Project Alpha');

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Project Alpha',
    ]);
});

it('adds the user as a conversation member', function () {
    Event::fake();

    $user = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        user: $user,
        name: 'Project Alpha',
    );

    expect($conversation->users)
        ->toHaveCount(1)
        ->first()->id->toBe($user->id);
});

it('requires a conversation name', function () {
    $user = User::factory()->create();

    app(CreateConversation::class)->handle(
        user: $user,
        name: '',
    );
})->throws(ValidationException::class);

it('makes the creator the conversation owner', function () {
    Event::fake();

    $user = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        user: $user,
        name: 'Project Alpha',
    );

    expect(
        $conversation->users()
            ->whereKey($user->id)
            ->first()
            ->pivot
            ->role
    )->toBe(ConversationRole::Owner);
});

// BROADCASTING

it('dispatches a conversation added event for the creator', function () {
    Event::fake();

    $user = User::factory()->create();

    $conversation = app(CreateConversation::class)->handle(
        user: $user,
        name: 'New conversation',
    );

    Event::assertDispatched(
        ConversationAdded::class,
        fn(ConversationAdded $event): bool =>
        $event->conversation->is($conversation)
            && $event->user->is($user),
    );
});
