<?php

namespace App\Enums;

enum ConversationRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
