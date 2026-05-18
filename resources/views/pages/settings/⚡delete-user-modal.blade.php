<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-ui.modal name="confirm-user-deletion" maxWidth="lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-foreground">Are you sure you want to delete your account?</h2>
            <p class="text-sm text-muted-foreground mt-2">
                Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
            </p>
        </div>

        <x-ui.input wire:model="password" label="Password" type="password" />

        <div class="flex justify-end gap-2">
            <x-ui.button type="button" variant="outline" @click="$dispatch('close-modal-confirm-user-deletion')">
                Cancel
            </x-ui.button>
            <x-ui.button variant="destructive" type="submit" data-test="confirm-delete-user-button">
                Delete account
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
