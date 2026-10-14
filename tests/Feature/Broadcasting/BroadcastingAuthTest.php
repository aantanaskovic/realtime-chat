<?php

use App\Models\Conversation;
use App\Models\User;

it('authorizes conversation members through the broadcasting endpoint', function () {
    $member = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $conversation->users()->attach($member);

    $response = $this->actingAs($member)->post(
        '/broadcasting/auth',
        [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversations.' . $conversation->id,
        ],
        [
            'Accept' => 'application/json',
        ],
    );

    $response->assertOk()
        ->assertJsonStructure(['auth']);
});

it('rejects non-members through the broadcasting endpoint', function () {
    $nonMember = User::factory()->create();
    $conversation = Conversation::factory()->create();

    $response = $this->actingAs($nonMember)->post(
        '/broadcasting/auth',
        [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversations.' . $conversation->id,
        ],
        [
            'Accept' => 'application/json',
        ],
    );

    $response->assertForbidden();
});
