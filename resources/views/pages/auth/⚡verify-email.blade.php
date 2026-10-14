<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public function resendVerificationEmail(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirect('/chat');

            return;
        }

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'A new verification link has been sent to your email address.');
    }
};
?>

<div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-md">

        <h1 class="text-2xl font-bold">
            Verify your email
        </h1>

        <p class="mt-2 text-sm text-gray-600">
            Thanks for signing up! Please verify your email address by clicking
            the link we sent you.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        <button type="button" wire:click="resendVerificationEmail"
            class="mt-6 w-full rounded-lg bg-indigo-600 px-4 py-2.5 font-medium text-white transition hover:bg-indigo-700">
            Resend verification email
        </button>

    </div>
</div>
