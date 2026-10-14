<?php

use App\Support\Validation\ConversationNameRules;
use Illuminate\Support\Facades\Validator;

it('defines the conversation name validation rules', function () {
    $validator = Validator::make(
        ['name' => 'Team chat'],
        ConversationNameRules::rules(),
        ConversationNameRules::messages(),
    );

    expect($validator->passes())->toBeTrue();
});

it('requires a conversation name', function () {
    $validator = Validator::make(
        ['name' => ''],
        ConversationNameRules::rules(),
        ConversationNameRules::messages(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('name'))
        ->toBe('The conversation name cannot be empty.');
});

it('limits the conversation name to 255 characters', function () {
    $validator = Validator::make(
        ['name' => str_repeat('a', 256)],
        ConversationNameRules::rules(),
        ConversationNameRules::messages(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('name'))
        ->toBe('The conversation name may not be greater than 255 characters.');
});

it('supports a custom field name', function () {
    $field = 'conversationName';

    $validator = Validator::make(
        [$field => 'Team chat'],
        ConversationNameRules::rules($field),
        ConversationNameRules::messages($field),
    );

    expect($validator->passes())->toBeTrue();
});

it('uses the custom field name in validation errors', function () {
    $field = 'editingConversationNameValue';

    $validator = Validator::make(
        [$field => ''],
        ConversationNameRules::rules($field),
        ConversationNameRules::messages($field),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first($field))
        ->toBe('The conversation name cannot be empty.');
});
