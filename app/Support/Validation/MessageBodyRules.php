<?php

namespace App\Support\Validation;

class MessageBodyRules
{
    public static function rules(string $field = 'body'): array
    {
        return [
            $field => ['required', 'string', 'max:5000'],
        ];
    }

    public static function messages(string $field = 'body'): array
    {
        return [
            "{$field}.required" => 'The message cannot be empty.',
            "{$field}.max" => 'The message may not be greater than 5000 characters.',
        ];
    }
}
