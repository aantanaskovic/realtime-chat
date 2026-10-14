<?php

namespace App\Events\Messages;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Message $message,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.' . $this->message->conversation_id),
            ...$this->message->conversation->users->map(
                fn(User $user) => new PrivateChannel('App.Models.User.' . $user->id),
            ),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'user_id' => $this->message->user_id,
                'body' => $this->message->body,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
