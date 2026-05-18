<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name  = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->fill($validated)->save();

        $this->dispatch('toast', message: 'Profile updated.');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout heading="Profile" subheading="Update your name and email address">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <x-ui.input wire:model="name" label="Name" type="text" required autofocus autocomplete="name" />
            <x-ui.input wire:model="email" label="Email" type="email" required autocomplete="email" />

            <div class="flex items-center gap-4">
                <x-ui.button type="submit" data-test="update-profile-button">
                    Save
                </x-ui.button>
            </div>
        </form>

        <livewire:pages::settings.delete-user-form />
    </x-pages::settings.layout>
</section>
