<?php

use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\User;
use App\Actions\Conversations\RemoveConversationMember;
use Illuminate\Auth\Access\AuthorizationException;

it('allows the owner to remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    app(RemoveConversationMember::class)->handle(
        actor: $owner,
        conversation: $conversation,
        user: $member,
    );

    expect($conversation->users()->whereKey($member->id)->exists())
        ->toBeFalse();
});

it('does not allow a regular member to remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $target = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $conversation->users()->attach($target, [
        'role' => ConversationRole::Member,
    ]);

    expect(fn() => app(RemoveConversationMember::class)->handle(
        actor: $member,
        conversation: $conversation,
        user: $target,
    ))->toThrow(AuthorizationException::class);

    expect($conversation->users()->whereKey($target->id)->exists())
        ->toBeTrue();
});

it('does not allow the owner to remove themselves', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(RemoveConversationMember::class)->handle(
        actor: $owner,
        conversation: $conversation,
        user: $owner,
    ))->toThrow(AuthorizationException::class);

    expect($conversation->users()->whereKey($owner->id)->exists())
        ->toBeTrue();
});

it('does not allow the owner to remove another owner', function () {
    $owner = User::factory()->create();
    $anotherOwner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($anotherOwner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(RemoveConversationMember::class)->handle(
        actor: $owner,
        conversation: $conversation,
        user: $anotherOwner,
    ))->toThrow(AuthorizationException::class);

    expect($conversation->users()->whereKey($anotherOwner->id)->exists())
        ->toBeTrue();
});

it('does nothing when the owner tries to remove a non-member', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    app(RemoveConversationMember::class)->handle(
        actor: $owner,
        conversation: $conversation,
        user: $nonMember,
    );

    expect($conversation->users()->whereKey($owner->id)->exists())
        ->toBeTrue()
        ->and($conversation->users()->whereKey($nonMember->id)->exists())
        ->toBeFalse();
});
