<?php

use App\Events\Conversations\ConversationDeleted;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

it('broadcasts to every member of the deleted conversation', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $conversation = Conversation::factory()->create();
    $conversation->users()->attach($owner);
    $conversation->users()->attach($member);

    $event = new ConversationDeleted(
        conversationId: $conversation->id,
        memberIds: [$owner->id, $member->id],
    );

    expect($event->broadcastOn())->toEqual([
        new PrivateChannel('App.Models.User.' . $owner->id),
        new PrivateChannel('App.Models.User.' . $member->id),
    ]);
});
