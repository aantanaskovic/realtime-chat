<?php

use App\Enums\ConversationRole;
use App\Events\Messages\MessageSent;
use App\Events\Messages\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

it('allows a conversation member to access the chat', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSuccessful()
        ->assertSet('conversation.id', $conversation->id);
});

it('does not allow a non-member to access the chat', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])->assertForbidden();
});

it('displays only messages belonging to the conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user);

    $otherConversation = Conversation::factory()->create();

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Message from this conversation.',
    ]);

    Message::factory()->create([
        'conversation_id' => $otherConversation->id,
        'body' => 'Message from another conversation.',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee($message->body)
        ->assertDontSee('Message from another conversation.');
});

it('sends a message from the chat', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('body', 'Hello from the chat.')
        ->call('sendMessage');

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Hello from the chat.',
    ]);
});

it('refreshes messages and clears the input after sending a message', function () {
    Event::fake([MessageSent::class]);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('body', 'Hello from the chat.')
        ->call('sendMessage')
        ->assertSet('body', '')
        ->assertCount('messages', 1)
        ->assertSee('Hello from the chat.');
});

it('displays the message author', function () {
    $user = User::factory()->create([
        'name' => 'Aleksandar',
    ]);

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Hello from Aleksandar.',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Aleksandar')
        ->assertSee('Hello from Aleksandar.');
});

it('displays messages in chronological order', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'First message.',
        'created_at' => now()->subMinute(),
    ]);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Second message.',
        'created_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSeeInOrder([
            'First message.',
            'Second message.',
        ]);
});

it('requires a message body', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('body', '')
        ->call('sendMessage')
        ->assertHasErrors(['body']);
});

it('does not create a message when the body is invalid', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('body', '')
        ->call('sendMessage')
        ->assertHasErrors(['body']);

    $this->assertDatabaseCount('messages', 0);
});

it('displays a validation error when the message body is empty', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('body', '')
        ->call('sendMessage')
        ->assertHasErrors(['body'])
        ->assertSee('The message cannot be empty.');
});

it('displays only conversations the user belongs to', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'My Conversation',
    ]);

    $conversation->users()->attach($user);

    $otherConversation = Conversation::factory()->create([
        'name' => 'Other Conversation',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('My Conversation')
        ->assertDontSee('Other Conversation');
});

it('allows the user to open chat without a selected conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->assertSuccessful()
        ->assertSet('conversation', null);
});

it('shows a prompt when no conversation is selected', function () {
    $user = User::factory()->create();

    Conversation::factory()->create([
        'name' => 'My Conversation',
    ])->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->assertSee('Select a conversation to continue');
});

it('displays the selected conversation name', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Project Alpha');
});

it('allows the user to select a conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->assertSet('conversation.id', $conversation->id);
});

it('does not allow a user to select a conversation they do not belong to', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->assertForbidden();
});

it('resets message editing state when selecting another conversation', function () {
    $user = User::factory()->create();

    $firstConversation = Conversation::factory()->create();
    $secondConversation = Conversation::factory()->create();

    $firstConversation->users()->attach($user);
    $secondConversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($firstConversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $firstConversation->id)
        ->call('startEditingMessage', $message->id)
        ->assertSet('editingMessageId', $message->id)
        ->assertSet('editingBody', 'Original message')
        ->call('selectConversation', $secondConversation->id)
        ->assertSet('conversation.id', $secondConversation->id)
        ->assertSet('editingMessageId', null)
        ->assertSet('editingBody', '');
});

it('creates a conversation from the chat', function () {
    Event::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->set('conversationName', 'Project Alpha')
        ->call('createConversation');

    $this->assertDatabaseHas('conversations', [
        'name' => 'Project Alpha',
    ]);
});


it('clears messages from the previous conversation when creating a new conversation', function () {
    Event::fake();

    $user = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Previous conversation',
    ]);

    $conversation->users()->attach($user);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Message from previous conversation.',
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat');

    $component->call('selectConversation', $conversation->id)
        ->assertSee('Message from previous conversation.')
        ->assertCount('messages', 1);

    $component
        ->set('conversationName', 'New conversation')
        ->call('createConversation')
        ->assertDontSee('Message from previous conversation.')
        ->assertCount('messages', 0);
});


it('selects the newly created conversation', function () {
    Event::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->set('conversationName', 'Project Alpha')
        ->call('createConversation')
        ->assertSet('conversation.name', 'Project Alpha');
});

it('shows the newly created conversation', function () {
    Event::fake();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->set('conversationName', 'Project Alpha')
        ->call('createConversation')
        ->assertSee('Project Alpha');
});

it('requires a conversation name', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->set('conversationName', '')
        ->call('createConversation')
        ->assertHasErrors(['conversationName']);

    $this->assertDatabaseCount('conversations', 0);
});

it('shows all users with their conversation membership status', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $nonMember = User::factory()->create([
        'name' => 'Non Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Owner User')
        ->assertSee('Owner')
        ->assertSee('Member User')
        ->assertSee('Member')
        ->assertSee('Non Member User')
        ->assertSee('Not a member');
});

it('shows conversation member controls to the owner', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Owner controls');
});

it('hides conversation member controls from regular members', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertDontSee('Manage members');
});

it('allows the owner to add a user to the conversation', function () {
    Event::fake();

    $owner = User::factory()->create();

    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('addMember', $user->id);

    expect($conversation->users()->whereKey($user->id)->exists())
        ->toBeTrue();
});

it('does not allow a regular member to add a user to the conversation', function () {
    $owner = User::factory()->create();

    $member = User::factory()->create();

    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('addMember', $user->id)
        ->assertForbidden();
});

it('shows an add control for non-members to the owner', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $nonMember = User::factory()->create([
        'name' => 'Non Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Add');
});

it('allows the owner to remove a member from the conversation', function () {
    $owner = User::factory()->create();

    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('removeMember', $member->id);

    expect($conversation->users()->whereKey($member->id)->exists())
        ->toBeFalse();
});

it('shows a remove control for members to the owner', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Remove');
});

it('does not show a remove control to regular members', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertDontSee('Remove');
});

it('does not show a remove control for the owner themselves', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Owner User')
        ->assertSee('Member User')
        ->assertSee('Remove');
});

it('updates the member status after adding a user', function () {
    Event::fake();

    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $user = User::factory()->create([
        'name' => 'New Member',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Not a member')
        ->call('addMember', $user->id)
        ->assertSee('member');
});

it('updates the member status after removing a user', function () {
    $owner = User::factory()->create([
        'name' => 'Owner User',
    ]);

    $member = User::factory()->create([
        'name' => 'Member User',
    ]);

    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('member')
        ->call('removeMember', $member->id)
        ->assertSee('Not a member');
});

it('allows a user to edit their own message', function () {
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

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('editMessage', $message->id, 'Updated message');

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

    $this->actingAs($anotherUser);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('editMessage', $message->id, 'Modified by another user')
        ->assertForbidden();

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'body' => 'Original message',
    ]);
});

it('shows the edit control only for the authors own messages', function () {
    $author = User::factory()->create();
    $anotherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($author);
    $conversation->users()->attach($anotherUser);

    $ownMessage = new Message([
        'body' => 'My message',
    ]);

    $ownMessage->conversation()->associate($conversation);
    $ownMessage->user()->associate($author);
    $ownMessage->save();

    $otherMessage = new Message([
        'body' => 'Their message',
    ]);

    $otherMessage->conversation()->associate($conversation);
    $otherMessage->user()->associate($anotherUser);
    $otherMessage->save();

    $this->actingAs($author);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Edit')
        ->assertSee('My message')
        ->assertSee('Their message');
});

it('opens the edit form with the existing message body', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->assertSet('editingMessageId', $message->id)
        ->assertSet('editingBody', 'Original message');
});

it('saves an edited message through the edit form', function () {
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

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', 'Updated message')
        ->call('editMessage', $message->id, 'Updated message')
        ->assertSee('Updated message')
        ->assertDontSee('Original message');

    expect($message->fresh()->body)->toBe('Updated message');
});

it('cancels editing a message without changing it', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', 'Unwanted change')
        ->call('cancelEditingMessage')
        ->assertSet('editingMessageId', null)
        ->assertSet('editingBody', '')
        ->assertSee('Original message');

    expect($message->fresh()->body)->toBe('Original message');
});

it('shows a validation error when an edited message is empty', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', '')
        ->call('saveEditedMessage')
        ->assertHasErrors(['editingBody' => 'required']);

    expect($message->fresh()->body)->toBe('Original message');
});

it('clears the edit validation error when the message body is updated', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', '')
        ->call('saveEditedMessage')
        ->assertHasErrors(['editingBody' => 'required'])
        ->set('editingBody', 'Updated message')
        ->assertHasNoErrors();
});

it('clears edit validation errors when editing is cancelled', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message([
        'body' => 'Original message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', '')
        ->call('saveEditedMessage')
        ->assertHasErrors(['editingBody' => 'required'])
        ->call('cancelEditingMessage')
        ->assertHasNoErrors();
});

it('does not save an edited message that has been deleted', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    $component = Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->call('startEditingMessage', $message->id)
        ->assertSet('editingMessageId', $message->id);

    $message->delete();

    $component
        ->set('editingBody', 'Updated message')
        ->call('saveEditedMessage')
        ->assertNotFound();
});

it('resets message editing state and validation errors when cancelling an edit', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', '')
        ->call('saveEditedMessage')
        ->assertHasErrors(['editingBody' => 'required'])
        ->call('cancelEditingMessage')
        ->assertSet('editingMessageId', null)
        ->assertSet('editingBody', '')
        ->assertHasNoErrors(['editingBody']);
});

it('clears the editing validation error when the user updates the message body', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->call('startEditingMessage', $message->id)
        ->set('editingBody', '')
        ->call('saveEditedMessage')
        ->assertHasErrors(['editingBody' => 'required'])
        ->set('editingBody', 'Corrected message')
        ->assertHasNoErrors(['editingBody'])
        ->assertSet('editingBody', 'Corrected message');
});

it('does not allow a user to edit another users message through the component', function () {
    $user = User::factory()->create();
    $author = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach([$user->id, $author->id]);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($author);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->call('editMessage', $message->id, 'Unauthorized edit')
        ->assertForbidden();

    expect($message->fresh()->body)->toBe('Original message');
});

it('does not allow a user to edit a message from another conversation', function () {
    $user = User::factory()->create();

    $firstConversation = Conversation::factory()->create();
    $secondConversation = Conversation::factory()->create();

    $firstConversation->users()->attach($user);
    $secondConversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($secondConversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $firstConversation->id)
        ->call('editMessage', $message->id, 'Unauthorized edit')
        ->assertNotFound();

    expect($message->fresh()->body)->toBe('Original message');
});

it('does not allow a user to start editing a message from another conversation', function () {
    $user = User::factory()->create();

    $firstConversation = Conversation::factory()->create();
    $secondConversation = Conversation::factory()->create();

    $firstConversation->users()->attach($user);
    $secondConversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($secondConversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $firstConversation->id)
        ->call('startEditingMessage', $message->id)
        ->assertNotFound()
        ->assertSet('editingMessageId', null)
        ->assertSet('editingBody', '');

    expect($message->fresh()->body)->toBe('Original message');
});

it('does not allow a user to send a message after being removed from the conversation', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner);
    $conversation->users()->attach($user);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id);

    $conversation->users()->detach($user);

    $component
        ->set('body', 'This should not be sent')
        ->call('sendMessage')
        ->assertForbidden();

    expect($conversation->messages()->exists())->toBeFalse();
});

it('does not allow a user to edit a message after being removed from the conversation', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner);
    $conversation->users()->attach($user);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $this->actingAs($user);

    $component = Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id);

    $conversation->users()->detach($user);

    $component
        ->call('editMessage', $message->id, 'Unauthorized edit')
        ->assertForbidden();

    expect($message->fresh()->body)->toBe('Original message');
});

it('does not send a message when no conversation is selected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->set('body', 'Hello from the test.')
        ->call('sendMessage')
        ->assertStatus(200);

    $this->assertDatabaseCount('messages', 0);
});

it('does not keep displaying messages after being removed from the conversation', function () {
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
        'body' => 'Private conversation message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($owner);
    $message->save();

    $this->actingAs($member);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])->assertSee('Private conversation message');

    $conversation->users()->detach($member);

    $component
        ->call('cancelEditingMessage')
        ->assertDontSee('Private conversation message');
});

it('does not allow starting to edit a message after being removed from the conversation', function () {
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
        'body' => 'Private conversation message',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($member);
    $message->save();

    $this->actingAs($member);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])->assertSee('Private conversation message');

    $conversation->users()->detach($member);

    $component
        ->call('startEditingMessage', $message->id)
        ->assertSet('conversation', null)
        ->assertSet('messages', fn($messages) => $messages->isEmpty())
        ->assertDontSee('Private conversation message');
});

it('clears the selected conversation when a removed member tries to add another member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $newMember = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, ['role' => ConversationRole::Owner]);
    $conversation->users()->attach($member, ['role' => ConversationRole::Member]);

    $message = new Message(['body' => 'Private conversation message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($owner);
    $message->save();

    $this->actingAs($member);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])->assertSee('Private conversation message');

    $conversation->users()->detach($member);

    $component
        ->call('addMember', $newMember->id)
        ->assertSet('conversation', null)
        ->assertDontSee('Private conversation message');

    expect($conversation->users()->whereKey($newMember->id)->exists())->toBeFalse();
});

it('clears the selected conversation when a removed member tries to remove another member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $otherMember = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, ['role' => ConversationRole::Owner]);
    $conversation->users()->attach($member, ['role' => ConversationRole::Member]);
    $conversation->users()->attach($otherMember, ['role' => ConversationRole::Member]);

    $message = new Message(['body' => 'Private conversation message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($owner);
    $message->save();

    $this->actingAs($member);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])->assertSee('Private conversation message');

    $conversation->users()->detach($member);

    $component
        ->call('removeMember', $otherMember->id)
        ->assertSet('conversation', null)
        ->assertDontSee('Private conversation message');

    expect($conversation->users()->whereKey($otherMember->id)->exists())->toBeTrue();
});

it('does not allow saving an edited message after being removed from the conversation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, ['role' => ConversationRole::Owner]);
    $conversation->users()->attach($member, ['role' => ConversationRole::Member]);

    $message = new Message(['body' => 'Original message']);
    $message->conversation()->associate($conversation);
    $message->user()->associate($member);
    $message->save();

    $this->actingAs($member);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('startEditingMessage', $message->id)
        ->assertSet('editingMessageId', $message->id)
        ->set('editingBody', 'Updated message');

    $conversation->users()->detach($member);

    $component
        ->call('saveEditedMessage')
        ->assertSet('conversation', null)
        ->assertDontSee('Updated message');

    expect($message->fresh()->body)->toBe('Original message');
});

it('does not allow a user to switch to a conversation they do not belong to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user, [
        'role' => ConversationRole::Owner,
    ]);

    $privateConversation = Conversation::factory()->create();
    $privateConversation->users()->attach($otherUser, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('conversation', $privateConversation)
        ->assertForbidden();
});

it('does not allow clearing the selected conversation through direct property updates', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('conversation', null)
        ->assertForbidden();
});

it('does not allow a member to add another member after tampering with member statuses', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $newMember = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->set('memberStatuses', [
            $owner->id => ConversationRole::Owner->value,
            $member->id => ConversationRole::Owner->value,
            $newMember->id => null,
        ])
        ->call('addMember', $newMember->id)
        ->assertForbidden();

    expect($conversation->users()->whereKey($newMember->id)->exists())->toBeFalse();
});

it('allows the owner to delete a conversation from the chat', function () {
    Event::fake();

    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('deleteConversation')
        ->assertSet('conversation', null)
        ->assertCount('messages', 0);

    $this->assertDatabaseMissing('conversations', [
        'id' => $conversation->id,
    ]);
});

it('does not allow a regular member to delete a conversation from the chat', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('deleteConversation')
        ->assertForbidden();

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
    ]);
});

it('shows the delete conversation button to the owner', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSee('Delete conversation');
});

it('does not show the delete conversation button to a regular member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $conversation->users()->attach($member, [
        'role' => ConversationRole::Member,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertDontSee('Delete conversation');
});

it('allows the conversation owner to rename the selected conversation', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->call('startEditingConversationName')
        ->set('editingConversationNameValue', 'Updated name')
        ->call('editConversation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Updated name',
    ]);
});

it('does not allow a regular member to rename the selected conversation', function () {
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

    $this->actingAs($member);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->set('conversationName', 'Unauthorized name')
        ->call('editConversation')
        ->assertForbidden();

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});

it('validates the conversation name when renaming', function () {
    $owner = User::factory()->create();

    $conversation = Conversation::factory()->create([
        'name' => 'Original name',
    ]);

    $conversation->users()->attach($owner, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($owner);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->set('conversationName', '')
        ->call('editConversation')
        ->assertHasErrors(['editingConversationNameValue' => 'required']);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'name' => 'Original name',
    ]);
});

// BROADCASTING

it('loads the selected conversation when the chat component mounts', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertSet('conversation.id', $conversation->id);
});

it('refreshes messages when a message is broadcast to the active conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ]);

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Message received in real time.',
    ]);

    $component
        ->call('refreshMessages', [
            'message' => [
                'conversation_id' => $conversation->id,
            ],
        ])
        ->assertSee('Message received in real time.')
        ->assertCount('messages', 1);
});

it('does not display messages from another conversation', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $anotherConversation = Conversation::factory()->create();

    $conversation->users()->attach($user);
    $anotherConversation->users()->attach($user);

    Message::factory()->create([
        'conversation_id' => $anotherConversation->id,
        'user_id' => $user->id,
        'body' => 'Message from another conversation.',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->assertCount('messages', 0)
        ->assertDontSee('Message from another conversation.');
});

it('registers the broadcast listener for the active conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('debugListeners')
        ->assertReturned([
            "echo-private:App.Models.User.{$user->id},.ConversationAdded" => 'refreshConversations',
        ]);
});


it('refreshes messages when a message is broadcast to a conversation selected after mounting', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat');

    $component->call('selectConversation', $conversation->id);

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Message received after selecting conversation.',
    ]);

    $component
        ->call('refreshMessages', [
            'message' => [
                'conversation_id' => $conversation->id,
            ],
        ])
        ->assertSee('Message received after selecting conversation.')
        ->assertCount('messages', 1);
});


it('does not refresh messages when a message is broadcast to an inactive conversation', function () {
    $user = User::factory()->create();

    $activeConversation = Conversation::factory()->create();
    $inactiveConversation = Conversation::factory()->create();

    $activeConversation->users()->attach($user);
    $inactiveConversation->users()->attach($user);

    $activeMessage = Message::factory()->create([
        'conversation_id' => $activeConversation->id,
        'user_id' => $user->id,
        'body' => 'Message in active conversation.',
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat');

    $component->call('selectConversation', $activeConversation->id);

    Message::factory()->create([
        'conversation_id' => $inactiveConversation->id,
        'user_id' => $user->id,
        'body' => 'Message in inactive conversation.',
    ]);

    $component
        ->call('refreshMessages', [
            'message' => [
                'conversation_id' => $inactiveConversation->id,
            ],
        ])
        ->assertSet('conversation.id', $activeConversation->id)
        ->assertSee('Message in active conversation.')
        ->assertDontSee('Message in inactive conversation.')
        ->assertCount('messages', 1);
});

it('shows a newly added conversation when the user receives a broadcast event', function () {
    $creator = User::factory()->create();
    $recipient = User::factory()->create();

    $this->actingAs($recipient);

    $component = Livewire::test('pages::chat')
        ->assertDontSee('New shared conversation');

    $conversation = Conversation::factory()->create([
        'name' => 'New shared conversation',
    ]);

    $conversation->users()->attach([
        $creator->id,
        $recipient->id,
    ]);

    $component
        ->dispatch(
            "echo-private:App.Models.User.{$recipient->id},.ConversationAdded",
        )
        ->assertSee('New shared conversation');
});

it('dispatches the active conversation changed event when selecting a conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->assertDispatched(
            'active-conversation-changed',
            conversationId: $conversation->id,
        );
});

it('dispatches the active conversation changed event when deleting a conversation', function () {
    Event::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user, [
        'role' => ConversationRole::Owner,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ])
        ->call('deleteConversation')
        ->assertDispatched(
            'active-conversation-changed',
            conversationId: null,
        );
});

it('clears the active conversation when it is deleted', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($user);

    Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'body' => 'Message in deleted conversation.',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::chat', ['conversation' => $conversation])
        ->call('handleConversationDeleted', [
            'conversationId' => $conversation->id,
        ])
        ->assertSet('conversation', null)
        ->assertCount('messages', 0)
        ->assertDontSee('Message in deleted conversation.');
});

it('can handle another Livewire request after the active conversation is deleted', function () {
    $user = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat', [
        'conversation' => $conversation,
    ]);

    $component
        ->call('handleConversationDeleted', [
            'conversationId' => $conversation->id,
        ])
        ->assertSet('conversation', null);

    $component
        ->call('refreshConversations')
        ->assertOk();
});

it('marks a conversation as read only for the current user when selected', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);
    $conversation->users()->attach($otherUser);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id)
        ->assertSet('conversation.id', $conversation->id);

    $userLastReadAt = DB::table('conversation_user')
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->value('last_read_at');

    $otherUserLastReadAt = DB::table('conversation_user')
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $otherUser->id)
        ->value('last_read_at');

    expect($userLastReadAt)->not->toBeNull()
        ->and($otherUserLastReadAt)->toBeNull();
});

it('displays an unread indicator when another user sends a message after the last read time', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);
    $conversation->users()->attach($otherUser);

    $messageTime = now()->subMinute();

    $message = new Message([
        'body' => 'An unread message.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($otherUser);
    $message->created_at = $messageTime;
    $message->save();

    DB::table('conversation_user')
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->update([
            'last_read_at' => now()->subMinutes(2),
        ]);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->assertSee('aria-label="Unread messages"', false);
});

it('does not display an unread indicator when the message was already read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);
    $conversation->users()->attach($otherUser);

    $message = new Message([
        'body' => 'An already read message.',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($otherUser);
    $message->created_at = now()->subMinutes(2);
    $message->save();

    DB::table('conversation_user')
        ->where('conversation_id', $conversation->id)
        ->where('user_id', $user->id)
        ->update([
            'last_read_at' => now()->subMinute(),
        ]);

    $this->actingAs($user);

    Livewire::test('pages::chat')
        ->assertDontSee('aria-label="Unread messages"', false);
});

it('broadcasts a sent message to the conversation and its participants', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);
    $conversation->users()->attach($otherUser);

    $message = new Message([
        'body' => 'Hello!',
    ]);

    $message->conversation()->associate($conversation);
    $message->user()->associate($user);
    $message->save();

    $event = new MessageSent($message);

    $channelNames = collect($event->broadcastOn())
        ->map(fn($channel) => $channel->name)
        ->all();

    expect($channelNames)
        ->toContain('private-conversations.' . $conversation->id)
        ->toContain('private-App.Models.User.' . $user->id)
        ->toContain('private-App.Models.User.' . $otherUser->id);
});

it('broadcasts a typing event when a conversation member starts typing', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id);

    Event::fake();

    $component->call('startTyping');

    Event::assertDispatched(UserTyping::class, function (UserTyping $event) use ($conversation, $user) {
        return $event->conversation->is($conversation)
            && $event->user->is($user);
    });
});

it('does not broadcast a typing event when the user is no longer a conversation member', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    $component = Livewire::test('pages::chat')
        ->call('selectConversation', $conversation->id);

    $conversation->users()->detach($user);

    Event::fake();

    $component->call('startTyping');

    Event::assertNotDispatched(UserTyping::class);
});

it('opens the conversation specified in the query string', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($user);

    $this->actingAs($user);

    Livewire::withQueryParams(['conversation' => $conversation->id])
        ->test('pages::chat')
        ->assertSuccessful()
        ->assertSet('conversation.id', $conversation->id)
        ->assertSet('activeConversationId', $conversation->id);
});

it('does not allow a non-member to open a conversation through the query string', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $this->actingAs($user);

    Livewire::withQueryParams(['conversation' => $conversation->id])
        ->test('pages::chat')
        ->assertSuccessful()
        ->assertSet('conversation', null)
        ->assertSet('activeConversationId', null);
});
