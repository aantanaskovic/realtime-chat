<?php

use App\Actions\Conversations\EditConversation;
use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('allows the owner to rename a conversation', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $updatedConversation = app(EditConversation::class)->handle(
        actor: $owner,
        conversation: $conversation,
        name: 'Updated name',
    );

    expect($updatedConversation->name)->toBe('Updated name');

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Updated name',
    ]);
});

it('does not allow a regular member to rename a conversation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    expect(fn() => app(EditConversation::class)->handle(
        actor: $member,
        conversation: $conversation,
        name: 'Unauthorized name',
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});

it('does not allow a non-member to rename a conversation', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(EditConversation::class)->handle(
        actor: $outsider,
        conversation: $conversation,
        name: 'Unauthorized name',
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});

it('requires a conversation name', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(EditConversation::class)->handle(
        actor: $owner,
        conversation: $conversation,
        name: '',
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});

it('does not allow a conversation name longer than 255 characters', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(EditConversation::class)->handle(
        actor: $owner,
        conversation: $conversation,
        name: str_repeat('a', 256),
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});

it('does not allow a conversation name containing only whitespace', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(EditConversation::class)->handle(
        actor: $owner,
        conversation: $conversation,
        name: '   ',
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});
