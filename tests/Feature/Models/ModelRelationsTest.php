<?php

use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;

it('user belongs to many conversations', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    expect($user->conversations)
        ->toHaveCount(1)
        ->and($user->conversations->first()->is($conversation))
        ->toBeTrue();
});

it('conversation belongs to many users', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    expect($conversation->users)
        ->toHaveCount(1)
        ->and($conversation->users->first()->is($user))
        ->toBeTrue();
});

it('user has many messages', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $message = new Message([
        'body' => 'Hello from the test.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    expect($user->messages)
        ->toHaveCount(1)
        ->and($user->messages->first()->is($message))
        ->toBeTrue();
});

it('conversation has many messages', function () {
    $conversation = Conversation::factory()->create();
    $user = User::factory()->create();

    $message = new Message([
        'body' => 'Hello from the test.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    expect($conversation->messages)
        ->toHaveCount(1)
        ->and($conversation->messages->first()->is($message))
        ->toBeTrue();
});

it('message belongs to a user', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $message = new Message([
        'body' => 'Hello from the test.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    expect($message->user)
        ->not->toBeNull()
        ->and($message->user->is($user))
        ->toBeTrue();
});

it('message belongs to a conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $message = new Message([
        'body' => 'Hello from the test.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    expect($message->conversation)
        ->not->toBeNull()
        ->and($message->conversation->is($conversation))
        ->toBeTrue();
});

it('casts conversation user role to the conversation role enum', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user, [
        'role' => ConversationRole::Owner,
    ]);

    $member = $conversation->users()->first();

    expect($member->pivot)
        ->toBeInstanceOf(ConversationUser::class);

    expect($member->pivot->role)
        ->toBe(ConversationRole::Owner);
});
