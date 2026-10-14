<?php

namespace App\Models;

use App\Enums\ConversationRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    protected function casts(): array
    {
        return [
            'role' => ConversationRole::class,
            'last_read_at' => 'datetime',
        ];
    }
}
