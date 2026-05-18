<?php

use App\Models\App;
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
        $this->dispatch('open-modal-app-form-modal');
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

        $this->dispatch('open-modal-app-form-modal');
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
        $this->dispatch('close-modal-app-form-modal');
        $this->dispatch('toast', message: $message);
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

        $this->dispatch('open-modal-app-delete-modal');
    }

    public function delete(): void
    {
        $app  = App::findOrFail($this->deletingId);
        $name = $app->name;
        $app->delete();

        Cache::forget('reverb_apps');
        $this->dispatch('close-modal-app-delete-modal');
        $this->dispatch('toast', message: "$name deleted.");
        $this->reset('deletingId', 'deletingName');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-foreground">Apps</h1>
            <p class="text-sm text-muted-foreground mt-1">Manage your Reverb applications</p>
        </div>
        <x-ui.button wire:click="openCreate" @click="$dispatch('open-modal-app-form-modal')">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New App
        </x-ui.button>
    </div>

    {{-- Card: Toolbar + Table + Footer --}}
    <x-ui.card class="overflow-hidden p-0">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 px-4 py-3 border-b border-border">
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">Show</span>
                <x-ui.select wire:model.live="perPage" class="w-20">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </x-ui.select>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search..."
                    class="w-56"
                />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-border">
                    <tr class="text-xs text-muted-foreground">
                        <th class="px-4 py-2.5 text-left font-medium w-10">No</th>
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
                            <th class="px-4 py-2.5 text-left font-medium">
                                @if ($def['sortable'])
                                    <button wire:click="sort('{{ $col }}')" class="flex items-center gap-1 hover:text-foreground transition-colors">
                                        {{ $def['label'] }}
                                        @if ($sortColumn === $col)
                                            <svg class="size-3 {{ $sortDirection === 'desc' ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                        @else
                                            <svg class="size-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/></svg>
                                        @endif
                                    </button>
                                @else
                                    {{ $def['label'] }}
                                @endif
                            </th>
                        @endforeach
                        <th class="px-4 py-2.5 w-24"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->apps as $app)
                        <tr class="border-b border-border/50 hover:bg-muted/30 transition-colors relative group">
                            <td class="px-4 py-2.5 text-xs text-muted-foreground tabular-nums">
                                {{ $this->apps->firstItem() + $loop->index }}
                            </td>
                            <td class="px-4 py-2.5 font-medium text-foreground">{{ $app->name }}</td>

                            {{-- App ID --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-1.5 font-mono text-xs text-muted-foreground" x-data="{ copied: false }">
                                    <span class="truncate max-w-[110px]" title="{{ $app->id }}">{{ $app->id }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $app->id }}'); copied = true; setTimeout(() => copied = false, 1500)" class="opacity-0 group-hover:opacity-100 shrink-0 hover:text-foreground transition-all" title="Copy">
                                        <svg x-show="!copied" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copied" class="size-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </td>

                            {{-- Key --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-1.5 font-mono text-xs text-muted-foreground" x-data="{ copied: false }">
                                    <span class="truncate max-w-[110px]" title="{{ $app->key }}">{{ $app->key }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $app->key }}'); copied = true; setTimeout(() => copied = false, 1500)" class="opacity-0 group-hover:opacity-100 shrink-0 hover:text-foreground transition-all" title="Copy">
                                        <svg x-show="!copied" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copied" class="size-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </td>

                            {{-- Secret --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-1.5 font-mono text-xs text-muted-foreground" x-data="{ copied: false }">
                                    <span class="truncate max-w-[110px]" title="{{ $app->secret }}">{{ $app->secret }}</span>
                                    <button @click="navigator.clipboard.writeText('{{ $app->secret }}'); copied = true; setTimeout(() => copied = false, 1500)" class="opacity-0 group-hover:opacity-100 shrink-0 hover:text-foreground transition-all" title="Copy">
                                        <svg x-show="!copied" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        <svg x-show="copied" class="size-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2.5">
                                @if ($app->is_active)
                                    <span class="inline-flex items-center rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-semibold text-white">
                                        ACTIVE
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-500 px-2 py-0.5 text-xs font-semibold text-white">
                                        INACTIVE
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end" x-data="{ open: false }" @click.outside="open = false">
                                    <button
                                        @click.stop="open = !open"
                                        class="inline-flex items-center gap-1 rounded-md border border-border px-2 py-1 text-xs font-medium text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                                    >
                                        Actions
                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        @click="open = false"
                                        class="absolute right-4 z-50 min-w-[8rem] rounded-md border border-border bg-card p-1 shadow-md"
                                        style="display: none"
                                    >
                                        <a href="{{ route('apps.show', $app->id) }}" wire:navigate class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-foreground hover:bg-accent transition-colors cursor-pointer">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Detail
                                        </a>
                                        <button wire:click="openEdit('{{ $app->id }}')" class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-foreground hover:bg-accent transition-colors cursor-pointer">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </button>
                                        <div class="h-px bg-border my-1"></div>
                                        <button wire:click="openDelete('{{ $app->id }}')" class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10 transition-colors cursor-pointer">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                {{ $search ? 'No apps match your search.' : 'No apps found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-border text-sm text-muted-foreground">
            <span>
                Showing {{ $this->apps->firstItem() ?? 0 }}–{{ $this->apps->lastItem() ?? 0 }} of {{ $this->apps->total() }} entries
            </span>
            @if ($this->apps->hasPages())
                {{ $this->apps->onEachSide(1)->links() }}
            @endif
        </div>

    </x-ui.card>

    {{-- Form Modal --}}
    <x-ui.modal name="app-form-modal" maxWidth="2xl">
        <form wire:submit="save">
            {{-- Header --}}
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-foreground">{{ $editingId ? 'Edit App' : 'New App' }}</h2>
                <p class="text-sm text-muted-foreground mt-0.5">{{ $editingId ? 'Update the Reverb application settings.' : 'Create a new Reverb application.' }}</p>
            </div>

            {{-- Scrollable body --}}
            <div class="max-h-[60vh] overflow-y-auto scrollbar-hide pr-1 space-y-5">

                {{-- Basic --}}
                <x-ui.input wire:model="name" label="Name" required placeholder="My App" />

                {{-- Connection --}}
                <div class="rounded-md border border-border p-4 space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Connection</p>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <x-ui.input wire:model="host" label="Host" placeholder="example.com" />
                        </div>
                        <x-ui.input wire:model="port" label="Port" type="number" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.select wire:model="scheme" label="Scheme">
                            <option value="https">https</option>
                            <option value="http">http</option>
                        </x-ui.select>
                        <x-ui.input wire:model="allowed_origins" label="Allowed Origins" placeholder="*" description="Comma-separated. Use * for all." />
                    </div>
                </div>

                {{-- Limits --}}
                <div class="rounded-md border border-border p-4 space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Limits</p>
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input wire:model="ping_interval" label="Ping Interval (s)" type="number" />
                        <x-ui.input wire:model="activity_timeout" label="Activity Timeout (s)" type="number" />
                        <x-ui.input wire:model="max_connections" label="Max Connections" type="number" placeholder="Unlimited" />
                        <x-ui.input wire:model="max_message_size" label="Max Message Size (bytes)" type="number" />
                    </div>
                    <x-ui.select wire:model="accept_client_events_from" label="Accept Client Events From">
                        <option value="members">members</option>
                        <option value="all">all</option>
                        <option value="none">none</option>
                    </x-ui.select>
                </div>

                {{-- Rate Limiting --}}
                <div class="rounded-md border border-border p-4 space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Rate Limiting</p>
                    <x-ui.checkbox wire:model.live="rate_limiting_enabled" label="Enable rate limiting" />
                    @if ($rate_limiting_enabled)
                        <div class="grid grid-cols-2 gap-3">
                            <x-ui.input wire:model="rate_limit_max_attempts" label="Max Attempts" type="number" />
                            <x-ui.input wire:model="rate_limit_decay_seconds" label="Decay (s)" type="number" />
                        </div>
                        <x-ui.checkbox wire:model="rate_limit_terminate_on_limit" label="Terminate connection on limit" />
                    @endif
                </div>

                <x-ui.checkbox wire:model="is_active" label="Active" />

            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 pt-5 mt-5 border-t border-border">
                <x-ui.button type="button" variant="outline" @click="$dispatch('close-modal-app-form-modal')">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled">
                    <svg wire:loading class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ $editingId ? 'Update' : 'Create' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Delete Modal --}}
    <x-ui.modal name="app-delete-modal" maxWidth="md">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-foreground">Delete App</h2>
                <p class="text-sm text-muted-foreground mt-1">
                    Are you sure you want to delete "{{ $deletingName }}"? This action cannot be undone.
                </p>
            </div>
            <div class="flex justify-end gap-3">
                <x-ui.button type="button" variant="outline" @click="$dispatch('close-modal-app-delete-modal')">
                    Cancel
                </x-ui.button>
                <x-ui.button variant="destructive" wire:click="delete">Delete</x-ui.button>
            </div>
        </div>
    </x-ui.modal>

</div>
