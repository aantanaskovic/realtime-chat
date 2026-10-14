<?php

use App\Actions\Conversations\DeleteConversation;
use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;

it('allows the owner to permanently delete a conversation', function () {
    Event::fake();

    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $message = new Message([
        'body' => 'Message to be deleted',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($owner);
    $message->save();

    app(DeleteConversation::class)->handle(
        actor: $owner,
        conversation: $conversation,
    );

    $this->assertDatabaseMissing('conversations', [
        'id' => $conversation->id,
    ]);

    $this->assertDatabaseMissing('messages', [
        'id' => $message->id,
    ]);

    $this->assertDatabaseMissing('conversation_user', [
        'conversation_id' => $conversation->id,
    ]);
});

it('does not allow a regular member to delete a conversation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    expect(fn() => app(DeleteConversation::class)->handle(
        actor: $member,
        conversation: $conversation,
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
    ]);
});

it('does not allow a non-member to delete a conversation', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    expect(fn() => app(DeleteConversation::class)->handle(
        actor: $outsider,
        conversation: $conversation,
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
    ]);
});
