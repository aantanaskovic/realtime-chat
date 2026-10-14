<?php

namespace Database\Seeders;

use App\Enums\ConversationRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $users = [
            'alice' => $this->createUser(
                'Alice Johnson',
                'alice@example.com',
            ),
            'bob' => $this->createUser(
                'Bob Williams',
                'bob@example.com',
            ),
            'charlie' => $this->createUser(
                'Charlie Brown',
                'charlie@example.com',
            ),
            'diana' => $this->createUser(
                'Diana Miller',
                'diana@example.com',
            ),
        ];

        $this->seedConversation(
            name: 'General Chat',
            participants: [
                [
                    'user' => $users['alice'],
                    'role' => ConversationRole::Owner,
                    'last_read_at' => $now->copy()->subMinutes(20),
                ],
                [
                    'user' => $users['bob'],
                    'role' => ConversationRole::Member,
                    'last_read_at' => $now,
                ],
                [
                    'user' => $users['charlie'],
                    'role' => ConversationRole::Member,
                    'last_read_at' => $now,
                ],
            ],
            messages: [
                [
                    'user' => $users['alice'],
                    'body' => 'Hey everyone! Welcome to the chat.',
                    'sent_at' => $now->copy()->subMinutes(25),
                ],
                [
                    'user' => $users['bob'],
                    'body' => 'Thanks! Realtime messaging is working.',
                    'sent_at' => $now->copy()->subMinutes(15),
                ],
                [
                    'user' => $users['charlie'],
                    'body' => 'Nice! Let us use this chat for updates.',
                    'sent_at' => $now->copy()->subMinutes(5),
                ],
            ],
        );

        $this->seedConversation(
            name: 'Project Team',
            participants: [
                [
                    'user' => $users['bob'],
                    'role' => ConversationRole::Owner,
                    'last_read_at' => $now,
                ],
                [
                    'user' => $users['alice'],
                    'role' => ConversationRole::Member,
                    'last_read_at' => $now->copy()->subMinutes(30),
                ],
                [
                    'user' => $users['diana'],
                    'role' => ConversationRole::Member,
                    'last_read_at' => $now,
                ],
            ],
            messages: [
                [
                    'user' => $users['bob'],
                    'body' => 'I have shared the latest project update.',
                    'sent_at' => $now->copy()->subMinutes(18),
                ],
                [
                    'user' => $users['diana'],
                    'body' => 'Great! I will review it today.',
                    'sent_at' => $now->copy()->subMinutes(12),
                ],
                [
                    'user' => $users['bob'],
                    'body' => 'Let me know if you have any questions.',
                    'sent_at' => $now->copy()->subMinutes(4),
                ],
            ],
        );

        $this->seedConversation(
            name: 'Weekend Plans',
            participants: [
                [
                    'user' => $users['charlie'],
                    'role' => ConversationRole::Owner,
                    'last_read_at' => $now,
                ],
                [
                    'user' => $users['bob'],
                    'role' => ConversationRole::Member,
                    'last_read_at' => null,
                ],
            ],
            messages: [
                [
                    'user' => $users['charlie'],
                    'body' => 'Anyone up for a trip this weekend?',
                    'sent_at' => $now->copy()->subMinutes(15),
                ],
                [
                    'user' => $users['charlie'],
                    'body' => 'We could go hiking if the weather is good.',
                    'sent_at' => $now->copy()->subMinutes(8),
                ],
            ],
        );
    }

    private function createUser(string $name, string $email): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
            ],
        );

        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }

    private function seedConversation(
        string $name,
        array $participants,
        array $messages,
    ): void {
        $conversation = Conversation::query()->firstOrCreate([
            'name' => $name,
        ]);

        foreach ($messages as $messageData) {
            $this->seedMessage(
                conversation: $conversation,
                user: $messageData['user'],
                body: $messageData['body'],
                sentAt: $messageData['sent_at'],
            );
        }

        foreach ($participants as $participant) {
            $user = $participant['user'];

            $pivotAttributes = [
                'role' => $participant['role']->value,
                'last_read_at' => $participant['last_read_at'],
            ];

            $conversation->users()->syncWithoutDetaching([
                $user->id => $pivotAttributes,
            ]);

            $conversation->users()->updateExistingPivot(
                $user->id,
                $pivotAttributes,
            );
        }
    }

    private function seedMessage(
        Conversation $conversation,
        User $user,
        string $body,
        CarbonInterface $sentAt,
    ): void {
        $message = $conversation->messages()
            ->where('user_id', $user->id)
            ->where('body', $body)
            ->first();

        $message ??= new Message(['body' => $body]);

        $message->conversation()->associate($conversation);
        $message->user()->associate($user);
        $message->created_at = $sentAt;
        $message->updated_at = $sentAt;
        $message->save();
    }
}
