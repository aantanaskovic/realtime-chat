<?php

use App\Models\Conversation;

it('has a name', function () {
    $conversation = Conversation::factory()->create([
        'name' => 'Project Alpha',
    ]);

    expect($conversation->name)->toBe('Project Alpha');
});
