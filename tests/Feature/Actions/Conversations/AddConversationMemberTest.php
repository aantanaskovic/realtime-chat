<?php

use App\Actions\Conversations\AddConversationMember;
use App\Enums\ConversationRole;
use App\Events\Conversations\ConversationAdded;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;

it('adds a user to a conversation', function () {
    Event::fake();

    $conversation = Conversation::factory()->create();

    $actor = User::factory()->create();
    $user = User::factory()->create();

    $conversation->users()->attach($actor, [
        'role' => ConversationRole::Owner,
    ]);

    app(AddConversationMember::class)->handle(
        actor: $actor,
        conversation: $conversation,
        user: $user,
    );

    expect($conversation->users()->whereKey($user->id)->exists())
        ->toBeTrue();
});

it('does not allow a non-member to add a user', function () {
    $conversation = Conversation::factory()->create();

    $nonMember = User::factory()->create();
    $userToAdd = User::factory()->create();

    app(AddConversationMember::class)->handle(
        actor: $nonMember,
        conversation: $conversation,
        user: $userToAdd,
    );
})->throws(AuthorizationException::class);

it('does not add a user who is already a conversation member', function () {
    $conversation = Conversation::factory()->create();

    $actor = User::factory()->create();
    $user = User::factory()->create();

    $conversation->users()->attach($actor, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($user, [
        'role' => ConversationRole::Member,
    ]);

    app(AddConversationMember::class)->handle(
        actor: $actor,
        conversation: $conversation,
        user: $user,
    );

    expect($conversation->users()->whereKey($user->id)->count())
        ->toBe(1);
});

it('adds a user as a conversation member', function () {
    Event::fake();

    $conversation = Conversation::factory()->create();

    $actor = User::factory()->create();
    $user = User::factory()->create();

    $conversation->users()->attach($actor, [
        'role' => ConversationRole::Owner,
    ]);

    app(AddConversationMember::class)->handle(
        actor: $actor,
        conversation: $conversation,
        user: $user,
    );

    $member = $conversation->users()
        ->whereKey($user->id)
        ->first();

    expect($member->pivot->role)
        ->toBe(ConversationRole::Member);
});

it('does not allow a regular member to add a user', function () {
    $conversation = Conversation::factory()->create();

    $member = User::factory()->create();
    $userToAdd = User::factory()->create();

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    app(AddConversationMember::class)->handle(
        actor: $member,
        conversation: $conversation,
        user: $userToAdd,
    );
})->throws(AuthorizationException::class);

it('preserves the role of an existing conversation member', function () {
    $conversation = Conversation::factory()->create();

    $actor = User::factory()->create();
    $user = User::factory()->create();

    $conversation->users()->attach($actor, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($user, [
        'role' => ConversationRole::Member,
    ]);

    app(AddConversationMember::class)->handle(
        actor: $actor,
        conversation: $conversation,
        user: $user,
    );

    $member = $conversation->users()
        ->whereKey($user->id)
        ->first();

    expect($member->pivot->role)
        ->toBe(ConversationRole::Member);
});

// BROADCASTING

it('dispatches a conversation added event for the new member', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    app(AddConversationMember::class)->handle(
        actor: $owner,
        conversation: $conversation,
        user: $member,
    );

    Event::assertDispatched(
        ConversationAdded::class,
        fn(ConversationAdded $event): bool =>
        $event->conversation->is($conversation)
            && $event->user->is($member),
    );
});
