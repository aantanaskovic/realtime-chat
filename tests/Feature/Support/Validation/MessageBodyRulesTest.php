<?php

use App\Support\Validation\MessageBodyRules;
use Illuminate\Support\Facades\Validator;

it('defines the message body validation rules', function () {
    $validator = Validator::make(
        ['body' => 'Valid message'],
        MessageBodyRules::rules(),
        MessageBodyRules::messages(),
    );

    expect($validator->passes())->toBeTrue();
});

it('requires a message body', function () {
    $validator = Validator::make(
        ['body' => ''],
        MessageBodyRules::rules(),
        MessageBodyRules::messages(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('body'))
        ->toBe('The message cannot be empty.');
});

it('limits the message body to 5000 characters', function () {
    $validator = Validator::make(
        ['body' => str_repeat('a', 5001)],
        MessageBodyRules::rules(),
        MessageBodyRules::messages(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('body'))
        ->toBe('The message may not be greater than 5000 characters.');
});
