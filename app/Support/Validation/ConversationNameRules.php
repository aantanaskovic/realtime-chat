<?php

namespace App\Support\Validation;

class ConversationNameRules
{
    public static function rules(string $field = 'name'): array
    {
        return [
            $field => ['required', 'string', 'max:255'],
        ];
    }

    public static function messages(string $field = 'name'): array
    {
        return [
            "{$field}.required" => 'The conversation name cannot be empty.',
            "{$field}.max" => 'The conversation name may not be greater than 255 characters.',
        ];
    }
}
