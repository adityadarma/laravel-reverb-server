<?php

use App\Models\App;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public ?int $appId = null;

    public string $name                     = '';
    public string $app_id                   = '';
    public string $key                      = '';
    public string $secret                   = '';
    public string $host                     = '';
    public int    $port                     = 443;
    public string $scheme                   = 'https';
    public string $allowed_origins          = '*';
    public int    $ping_interval            = 60;
    public int    $activity_timeout         = 30;
    public ?int   $max_connections          = null;
    public int    $max_message_size         = 10000;
    public string $accept_client_events_from = 'members';
    public bool   $rate_limiting_enabled    = false;
    public int    $rate_limit_max_attempts  = 60;
    public int    $rate_limit_decay_seconds = 60;
    public bool   $rate_limit_terminate_on_limit = false;
    public bool   $is_active               = true;

    #[On('open-create-form')]
    public function openCreate(): void
    {
        $this->reset();
        $this->app_id = (string) Str::uuid();
        $this->key    = Str::random(20);
        $this->secret = Str::random(40);

        Flux::modal('app-form-modal')->show();
    }

    #[On('open-edit-form')]
    public function openEdit(int $id): void
    {
        $app = App::findOrFail($id);

        $this->appId                       = $app->id;
        $this->name                        = $app->name;
        $this->app_id                      = $app->app_id;
        $this->key                         = $app->key;
        $this->secret                      = $app->secret;
        $this->host                        = $app->host ?? '';
        $this->port                        = $app->port;
        $this->scheme                      = $app->scheme;
        $this->allowed_origins             = implode(',', $app->allowed_origins ?? ['*']);
        $this->ping_interval               = $app->ping_interval;
        $this->activity_timeout            = $app->activity_timeout;
        $this->max_connections             = $app->max_connections;
        $this->max_message_size            = $app->max_message_size;
        $this->accept_client_events_from   = $app->accept_client_events_from;
        $this->rate_limiting_enabled       = $app->rate_limiting_enabled;
        $this->rate_limit_max_attempts     = $app->rate_limit_max_attempts;
        $this->rate_limit_decay_seconds    = $app->rate_limit_decay_seconds;
        $this->rate_limit_terminate_on_limit = $app->rate_limit_terminate_on_limit;
        $this->is_active                   = $app->is_active;

        Flux::modal('app-form-modal')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name'                        => ['required', 'string', 'max:255'],
            'app_id'                      => ['required', 'string', 'max:255'],
            'key'                         => ['required', 'string', 'max:255'],
            'secret'                      => ['required', 'string', 'max:255'],
            'host'                        => ['nullable', 'string', 'max:255'],
            'port'                        => ['required', 'integer', 'min:1', 'max:65535'],
            'scheme'                      => ['required', 'in:http,https'],
            'allowed_origins'             => ['required', 'string'],
            'ping_interval'               => ['required', 'integer', 'min:1'],
            'activity_timeout'            => ['required', 'integer', 'min:1'],
            'max_connections'             => ['nullable', 'integer', 'min:1'],
            'max_message_size'            => ['required', 'integer', 'min:1'],
            'accept_client_events_from'   => ['required', 'string'],
            'rate_limiting_enabled'       => ['boolean'],
            'rate_limit_max_attempts'     => ['required', 'integer', 'min:1'],
            'rate_limit_decay_seconds'    => ['required', 'integer', 'min:1'],
            'rate_limit_terminate_on_limit' => ['boolean'],
            'is_active'                   => ['boolean'],
        ]);

        // Convert comma-separated origins to array
        $validated['allowed_origins'] = array_map(
            'trim',
            explode(',', $validated['allowed_origins'])
        );

        if ($this->appId) {
            App::findOrFail($this->appId)->update($validated);
            $message = 'App updated successfully.';
        } else {
            App::create($validated);
            $message = 'App created successfully.';
        }

        Cache::forget('reverb_apps');

        Flux::modal('app-form-modal')->close();
        Flux::toast(variant: 'success', text: $message);

        $this->dispatch('app-saved');
    }

    public function regenerateKey(): void
    {
        $this->key = Str::random(20);
    }

    public function regenerateSecret(): void
    {
        $this->secret = Str::random(40);
    }
}; ?>

<div>
    <flux:modal name="app-form-modal" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $appId ? 'Edit App' : 'New App' }}
                </flux:heading>
                <flux:subheading>
                    {{ $appId ? 'Update the Reverb application settings.' : 'Create a new Reverb application.' }}
                </flux:subheading>
            </div>

            {{-- Basic Info --}}
            <div class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">Basic Info</flux:heading>

                <flux:input wire:model="name" label="Name" required placeholder="My App" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="app_id" label="App ID" required />
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <flux:input wire:model="key" label="Key" required />
                        </div>
                        <flux:button type="button" size="sm" variant="filled" wire:click="regenerateKey" icon="arrow-path" />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <flux:input wire:model="secret" label="Secret" required />
                    </div>
                    <flux:button type="button" size="sm" variant="filled" wire:click="regenerateSecret" icon="arrow-path" />
                </div>
            </div>

            {{-- Connection --}}
            <div class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">Connection</flux:heading>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <flux:input wire:model="host" label="Host" placeholder="example.com" />
                    </div>
                    <flux:input wire:model="port" label="Port" type="number" />
                </div>

                <flux:select wire:model="scheme" label="Scheme">
                    <flux:select.option value="https">https</flux:select.option>
                    <flux:select.option value="http">http</flux:select.option>
                </flux:select>

                <flux:input wire:model="allowed_origins" label="Allowed Origins" placeholder="* or https://example.com,https://app.com" description="Comma-separated list of allowed origins. Use * for all." />
            </div>

            {{-- Limits --}}
            <div class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">Limits</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="ping_interval" label="Ping Interval (s)" type="number" />
                    <flux:input wire:model="activity_timeout" label="Activity Timeout (s)" type="number" />
                    <flux:input wire:model="max_connections" label="Max Connections" type="number" placeholder="Unlimited" />
                    <flux:input wire:model="max_message_size" label="Max Message Size (bytes)" type="number" />
                </div>

                <flux:select wire:model="accept_client_events_from" label="Accept Client Events From">
                    <flux:select.option value="members">members</flux:select.option>
                    <flux:select.option value="all">all</flux:select.option>
                    <flux:select.option value="none">none</flux:select.option>
                </flux:select>
            </div>

            {{-- Rate Limiting --}}
            <div class="space-y-4">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wide">Rate Limiting</flux:heading>

                <flux:checkbox wire:model.live="rate_limiting_enabled" label="Enable rate limiting" />

                @if ($rate_limiting_enabled)
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="rate_limit_max_attempts" label="Max Attempts" type="number" />
                        <flux:input wire:model="rate_limit_decay_seconds" label="Decay (s)" type="number" />
                    </div>
                    <flux:checkbox wire:model="rate_limit_terminate_on_limit" label="Terminate connection on limit" />
                @endif
            </div>

            {{-- Status --}}
            <flux:checkbox wire:model="is_active" label="Active" />

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $appId ? 'Update' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
