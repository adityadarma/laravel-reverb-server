<?php

use App\Models\App;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int    $appId = null;
    public ?string $appName = null;

    #[On('open-delete-confirm')]
    public function open(int $id): void
    {
        $app = App::findOrFail($id);

        $this->appId   = $app->id;
        $this->appName = $app->name;

        Flux::modal('app-delete-modal')->show();
    }

    public function delete(): void
    {
        $app = App::findOrFail($this->appId);
        $app->delete();

        Cache::forget('reverb_apps');

        Flux::modal('app-delete-modal')->close();
        Flux::toast(variant: 'success', text: "{$this->appName} deleted.");

        $this->reset();
        $this->dispatch('app-saved');
    }
}; ?>

<div>
<flux:modal name="app-delete-modal" class="max-w-md">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Delete App</flux:heading>
            <flux:subheading>
                Are you sure you want to delete "{{ $appName }}"? This action cannot be undone.
            </flux:subheading>
        </div>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="filled">Cancel</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" wire:click="delete">
                Delete
            </flux:button>
        </div>
    </div>
</flux:modal>
</div>
