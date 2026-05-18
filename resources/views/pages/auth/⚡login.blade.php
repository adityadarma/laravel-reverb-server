<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Log in')] #[Layout('layouts.auth')] class extends Component {
    public string $email    = '';
    public string $password = '';
    public bool   $remember = false;

    public function login(): void
    {
        $this->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();
        $this->redirect('/', navigate: false);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div class="space-y-5">
    <div class="space-y-1 text-center">
        <h2 class="text-xl font-semibold text-foreground">Sign in</h2>
        <p class="text-sm text-muted-foreground">Enter your credentials to continue</p>
    </div>

    @if (session('status'))
        <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-400">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <x-ui.input
            wire:model="email"
            label="Email"
            type="email"
            placeholder="you@example.com"
            autocomplete="email"
            autofocus
            :error="$errors->first('email')"
        />

        <x-ui.input
            wire:model="password"
            label="Password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            :error="$errors->first('password')"
        />

        <div class="flex items-center gap-2">
            <x-ui.checkbox wire:model="remember" id="remember" label="Remember me" />
        </div>

        <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
            <svg wire:loading class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in...</span>
        </x-ui.button>
    </form>
</div>
