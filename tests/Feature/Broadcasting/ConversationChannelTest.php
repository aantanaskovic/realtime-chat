<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;

it('registers the conversation private channel', function () {
    $channels = app(BroadcastManager::class)
        ->driver()
        ->getChannels();

    expect($channels)
        ->toHaveKey('conversations.{conversationId}');
});

it('allows conversation members to access the private channel', function () {
    $member = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($member);

    $channels = app(BroadcastManager::class)
        ->driver()
        ->getChannels();

    $callback = $channels['conversations.{conversationId}'];

    expect($callback($member, $conversation->id))->toBeTrue();
});

it('denies users who are not conversation members', function () {
    $nonMember = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $channels = app(BroadcastManager::class)
        ->driver()
        ->getChannels();

    $callback = $channels['conversations.{conversationId}'];

    expect($callback($nonMember, $conversation->id))->toBeFalse();
});
