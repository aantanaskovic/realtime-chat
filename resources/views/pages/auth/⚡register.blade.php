<?php

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.guest')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(CreateNewUser $creator): void
    {
        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect('/chat');
    }
};
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-md">

        <h1 class="text-2xl font-bold">
            Create your account
        </h1>

        <p class="mt-2 text-sm text-gray-600">
            Create an account to start chatting.
        </p>

        <form wire:submit="register" class="mt-6 space-y-5">

            <div>
                <label for="name" class="block text-sm font-medium">
                    Name
                </label>

                <input id="name" type="text" wire:model="name" autocomplete="name"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">

                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">
                    Email
                </label>

                <input id="email" type="email" wire:model="email" autocomplete="email"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">

                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">
                    Password
                </label>

                <input id="password" type="password" wire:model="password" autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">

                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">
                    Confirm password
                </label>

                <input id="password_confirmation" type="password" wire:model="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white hover:bg-indigo-700">
                Create account
            </button>

        </form>
    </div>
</div>
