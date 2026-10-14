<?php

namespace App\Events\Conversations;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $conversationId,
        public array $memberIds,
    ) {}

    public function broadcastOn(): array
    {
        return collect($this->memberIds)
            ->map(
                fn(int $memberId) => new PrivateChannel(
                    'App.Models.User.' . $memberId,
                ),
            )
            ->all();
    }

    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationDeleted';
    }
}
