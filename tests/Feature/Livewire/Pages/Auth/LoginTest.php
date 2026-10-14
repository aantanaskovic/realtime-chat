<?php

use App\Models\User;
use Livewire\Livewire;

it('allows a guest to open the login page', function () {
    Livewire::test('pages::auth.login')
        ->assertSuccessful();
});

it('allows a user to log in', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', 'test@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

it('redirects an authenticated user away from the login page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('login'))
        ->assertRedirect();
});
