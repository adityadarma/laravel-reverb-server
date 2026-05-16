<?php

use App\Models\App;
use Flux\Flux;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Apps')] class extends Component {
    use WithPagination;

    // ── Table state (synced to URL) ────────────────────────────────────────────
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortColumn = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    #[Url(as: 'per_page')]
    public int $perPage = 10;

    // ── Form modal ────────────────────────────────────────────────────────────
    public ?string $editingId                    = null;
    public string $name                         = '';
    public string $host                         = '';
    public int    $port                         = 443;
    public string $scheme                       = 'https';
    public string $allowed_origins              = '*';
    public int    $ping_interval                = 60;
    public int    $activity_timeout             = 30;
    public ?int   $max_connections              = null;
    public int    $max_message_size             = 10000;
    public string $accept_client_events_from    = 'members';
    public bool   $rate_limiting_enabled        = false;
    public int    $rate_limit_max_attempts      = 60;
    public int    $rate_limit_decay_seconds     = 60;
    public bool   $rate_limit_terminate_on_limit = false;
    public bool   $is_active                    = true;

    // ── Delete modal ──────────────────────────────────────────────────────────
    public ?string $deletingId   = null;
    public ?string $deletingName = null;

    // ── Sortable columns whitelist ────────────────────────────────────────────
    protected array $sortable = ['name', 'is_active', 'created_at'];

    // ── Computed ──────────────────────────────────────────────────────────────
    #[Computed(persist: false)]
    public function apps()
    {
        return App::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);
    }

    // ── Table actions ─────────────────────────────────────────────────────────
    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn    = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // ── Form modal actions ────────────────────────────────────────────────────
    public function openCreate(): void
    {
        $this->resetForm();
        Flux::modal('app-form-modal')->show();
    }

    public function openEdit(string $id): void
    {
        $app = App::findOrFail($id);

        $this->editingId                     = $app->id;
        $this->name                          = $app->name;
        $this->host                          = $app->host ?? '';
        $this->port                          = $app->port;
        $this->scheme                        = $app->scheme;
        $this->allowed_origins               = implode(',', $app->allowed_origins ?? ['*']);
        $this->ping_interval                 = $app->ping_interval;
        $this->activity_timeout              = $app->activity_timeout;
        $this->max_connections               = $app->max_connections;
        $this->max_message_size              = $app->max_message_size;
        $this->accept_client_events_from     = $app->accept_client_events_from;
        $this->rate_limiting_enabled         = $app->rate_limiting_enabled;
        $this->rate_limit_max_attempts       = $app->rate_limit_max_attempts;
        $this->rate_limit_decay_seconds      = $app->rate_limit_decay_seconds;
        $this->rate_limit_terminate_on_limit = $app->rate_limit_terminate_on_limit;
        $this->is_active                     = $app->is_active;

        Flux::modal('app-form-modal')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name'                           => ['required', 'string', 'max:255'],
            'host'                           => ['nullable', 'string', 'max:255'],
            'port'                           => ['required', 'integer', 'min:1', 'max:65535'],
            'scheme'                         => ['required', 'in:http,https'],
            'allowed_origins'                => ['required', 'string'],
            'ping_interval'                  => ['required', 'integer', 'min:1'],
            'activity_timeout'               => ['required', 'integer', 'min:1'],
            'max_connections'                => ['nullable', 'integer', 'min:1'],
            'max_message_size'               => ['required', 'integer', 'min:1'],
            'accept_client_events_from'      => ['required', 'string'],
            'rate_limiting_enabled'          => ['boolean'],
            'rate_limit_max_attempts'        => ['required', 'integer', 'min:1'],
            'rate_limit_decay_seconds'       => ['required', 'integer', 'min:1'],
            'rate_limit_terminate_on_limit'  => ['boolean'],
            'is_active'                      => ['boolean'],
        ]);

        $validated['allowed_origins'] = array_map('trim', explode(',', $validated['allowed_origins']));

        if ($this->editingId) {
            App::findOrFail($this->editingId)->update($validated);
            $message = 'App updated successfully.';
        } else {
            $validated['key']    = Str::random(20);
            $validated['secret'] = Str::random(40);
            App::create($validated);
            $message = 'App created successfully.';
        }

        Cache::forget('reverb_apps');
        Flux::modal('app-form-modal')->close();
        Flux::toast(variant: 'success', text: $message);
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'host',
            'allowed_origins', 'max_connections',
            'rate_limiting_enabled', 'rate_limit_terminate_on_limit',
        ]);
        $this->port                      = 443;
        $this->scheme                    = 'https';
        $this->allowed_origins           = '*';
        $this->ping_interval             = 60;
        $this->activity_timeout          = 30;
        $this->max_message_size          = 10000;
        $this->accept_client_events_from = 'members';
        $this->rate_limit_max_attempts   = 60;
        $this->rate_limit_decay_seconds  = 60;
        $this->is_active                 = true;
    }

    // ── Delete modal actions ──────────────────────────────────────────────────
    public function openDelete(string $id): void
    {
        $app = App::findOrFail($id);

        $this->deletingId   = $app->id;
        $this->deletingName = $app->name;

        Flux::modal('app-delete-modal')->show();
    }

    public function delete(): void
    {
        $app  = App::findOrFail($this->deletingId);
        $name = $app->name;
        $app->delete();

        Cache::forget('reverb_apps');
        Flux::modal('app-delete-modal')->close();
        Flux::toast(variant: 'success', text: "$name deleted.");
        $this->reset('deletingId', 'deletingName');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Apps</flux:heading>
            <flux:subheading>Manage your Reverb applications</flux:subheading>
        </div>
        <flux:modal.trigger name="app-form-modal">
            <flux:button variant="primary" icon="plus" wire:click="openCreate">
                New App
            </flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Card: Toolbar + Table + Footer --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">Show</span>
                <flux:select wire:model.live="perPage" class="w-20">
                    <flux:select.option value="10">10</flux:select.option>
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>
            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search..."
                    icon="magnifying-glass"
                    clearable
                    class="w-56"
                />
            </div>
        </div>

        {{-- Table --}}
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                <tr>
                    <th class="px-4 py-3 text-start font-medium w-12">No</th>
                    @php
                        $cols = [
                            'name'      => ['label' => 'Name',    'sortable' => true],
                            'id'        => ['label' => 'App ID',  'sortable' => false],
                            'key'       => ['label' => 'Key',     'sortable' => false],
                            'secret'    => ['label' => 'Secret',  'sortable' => false],
                            'is_active' => ['label' => 'Status',  'sortable' => true],
                        ];
                    @endphp
                    @foreach ($cols as $col => $def)
                        <th class="px-4 py-3 text-start">
                            @if ($def['sortable'])
                                <button
                                    wire:click="sort('{{ $col }}')"
                                    class="flex items-center gap-1 font-medium hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors"
                                >
                                    {{ $def['label'] }}
                                    @if ($sortColumn === $col)
                                        <flux:icon.chevron-up @class(['size-3', 'rotate-180' => $sortDirection === 'desc']) />
                                    @else
                                        <flux:icon.chevrons-up-down class="size-3 opacity-40" />
                                    @endif
                                </button>
                            @else
                                <span class="font-medium">{{ $def['label'] }}</span>
                            @endif
                        </th>
                    @endforeach
                    <th class="px-4 py-3 text-end font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($this->apps as $app)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition-colors">
                        <td class="px-4 py-3 text-zinc-400 dark:text-zinc-500 tabular-nums">
                            {{ $this->apps->firstItem() + $loop->index }}
                        </td>
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $app->name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5 font-mono text-xs text-zinc-600 dark:text-zinc-400"
                                x-data="{ copied: false }"
                            >
                                <span class="truncate max-w-[120px]" title="{{ $app->id }}">{{ $app->id }}</span>
                                <button
                                    @click="navigator.clipboard.writeText('{{ $app->id }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                                    title="Copy"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" class="size-3.5" />
                                    <flux:icon.check x-show="copied" class="size-3.5 text-green-500" />
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5 font-mono text-xs text-zinc-600 dark:text-zinc-400"
                                x-data="{ copied: false }"
                            >
                                <span class="truncate max-w-[120px]" title="{{ $app->key }}">{{ $app->key }}</span>
                                <button
                                    @click="navigator.clipboard.writeText('{{ $app->key }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                                    title="Copy"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" class="size-3.5" />
                                    <flux:icon.check x-show="copied" class="size-3.5 text-green-500" />
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5 font-mono text-xs text-zinc-600 dark:text-zinc-400"
                                x-data="{ copied: false }"
                            >
                                <span class="truncate max-w-[120px]" title="{{ $app->secret }}">{{ $app->secret }}</span>
                                <button
                                    @click="navigator.clipboard.writeText('{{ $app->secret }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                                    title="Copy"
                                >
                                    <flux:icon.document-duplicate x-show="!copied" class="size-3.5" />
                                    <flux:icon.check x-show="copied" class="size-3.5 text-green-500" />
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge :variant="$app->is_active ? 'success' : 'danger'" size="sm">
                                {{ $app->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item icon="eye" :href="route('apps.show', $app->id)" wire:navigate>
                                            Detail
                                        </flux:menu.item>
                                        <flux:menu.item icon="pencil" wire:click="openEdit('{{ $app->id }}')">
                                            Edit
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="openDelete('{{ $app->id }}')">
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">
                            {{ $search ? 'No apps match your search.' : 'No apps found.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-zinc-200 dark:border-zinc-700 text-sm text-zinc-500 dark:text-zinc-400">
            <span>
                Showing {{ $this->apps->firstItem() ?? 0 }}–{{ $this->apps->lastItem() ?? 0 }} of {{ $this->apps->total() }} entries
            </span>
            @if ($this->apps->hasPages())
                {{ $this->apps->onEachSide(1)->links() }}
            @endif
        </div>

    </div>

    {{-- Form Modal --}}
    <flux:modal name="app-form-modal" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Edit App' : 'New App' }}</flux:heading>
                <flux:subheading>{{ $editingId ? 'Update the Reverb application settings.' : 'Create a new Reverb application.' }}</flux:subheading>
            </div>

            {{-- Basic Info --}}
            <div class="space-y-4">
                <flux:input wire:model="name" label="Name" required placeholder="My App" />
            </div>

            {{-- Connection --}}
            <div class="space-y-4">
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
                <flux:input wire:model="allowed_origins" label="Allowed Origins" placeholder="* or https://example.com,https://app.com" description="Comma-separated. Use * for all." />
            </div>

            {{-- Limits --}}
            <div class="space-y-4">
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
                <flux:checkbox wire:model.live="rate_limiting_enabled" label="Enable rate limiting" />
                @if ($rate_limiting_enabled)
                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="rate_limit_max_attempts" label="Max Attempts" type="number" />
                        <flux:input wire:model="rate_limit_decay_seconds" label="Decay (s)" type="number" />
                    </div>
                    <flux:checkbox wire:model="rate_limit_terminate_on_limit" label="Terminate on limit" />
                @endif
            </div>

            <flux:checkbox wire:model="is_active" label="Active" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Update' : 'Create' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal name="app-delete-modal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete App</flux:heading>
                <flux:subheading>
                    Are you sure you want to delete "{{ $deletingName }}"? This action cannot be undone.
                </flux:subheading>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">Delete</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
