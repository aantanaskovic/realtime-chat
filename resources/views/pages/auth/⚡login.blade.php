<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
        ];

        if (!Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'The provided credentials are incorrect.');

            return;
        }

        session()->regenerate();

        $this->redirectIntended(route('dashboard'));
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 p-6">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-md">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">
                Log in
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Sign in to your account.
            </p>
        </div>

        <form wire:submit="login" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">
                    Email
                </label>

                <input id="email" type="email" wire:model="email" autocomplete="email"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">

                @error('email')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">
                    Password
                </label>

                <input id="password" type="password" wire:model="password" autocomplete="current-password"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">

                @error('password')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" wire:model="remember"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">

                <span class="text-sm text-gray-600">
                    Remember me
                </span>
            </label>

            <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Log in
            </button>
        </form>
    </div>
</div>
