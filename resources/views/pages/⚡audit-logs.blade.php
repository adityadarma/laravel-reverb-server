<?php

use App\Models\AuditLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Audit Logs')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'action')]
    public string $filterAction = '';

    #[Url(as: 'per_page')]
    public int $perPage = 25;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterAction(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->resetPage(); }

    #[Computed(persist: false)]
    public function logs()
    {
        return AuditLog::query()
            ->with('user')
            ->when($this->search, fn ($q) =>
                $q->where('auditable_name', 'like', "%{$this->search}%")
                  ->orWhere('user_name', 'like', "%{$this->search}%")
                  ->orWhere('ip_address', 'like', "%{$this->search}%")
            )
            ->when($this->filterAction, fn ($q) => $q->where('action', $this->filterAction))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-foreground">Audit Logs</h1>
            <p class="text-sm text-muted-foreground mt-1">Track all changes made to your Reverb applications</p>
        </div>
    </div>

    <x-ui.card class="overflow-hidden p-0">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between gap-4 px-4 py-3 border-b border-border flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">Show</span>
                <select wire:model.live="perPage" class="h-8 rounded-md border border-input bg-background px-2 text-sm">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <select wire:model.live="filterAction" class="h-8 rounded-md border border-input bg-background px-2 text-sm text-muted-foreground">
                    <option value="">All actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="rotated_key">Key Rotated</option>
                    <option value="rotated_secret">Secret Rotated</option>
                </select>
            </div>
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search app, user, IP..." class="w-64"/>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto" x-data="{ expanded: {} }">
            <table class="w-full text-sm">
                <thead class="border-b border-border">
                    <tr class="text-xs text-muted-foreground">
                        <th class="px-4 py-2.5 text-left font-medium">Time</th>
                        <th class="px-4 py-2.5 text-left font-medium">Action</th>
                        <th class="px-4 py-2.5 text-left font-medium">App</th>
                        <th class="px-4 py-2.5 text-left font-medium">User</th>
                        <th class="px-4 py-2.5 text-left font-medium">IP Address</th>
                        <th class="px-4 py-2.5 text-left font-medium w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->logs as $i => $log)
                        <tr class="border-b border-border/50 hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-2.5 text-xs text-muted-foreground whitespace-nowrap">
                                <span title="{{ $log->created_at->toDateTimeString() }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                <x-ui.badge :variant="$log->action_color">{{ $log->action_label }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-2.5 font-medium text-foreground">
                                {{ $log->auditable_name ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-muted-foreground">
                                {{ $log->user_name ?? 'System' }}
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs text-muted-foreground">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($log->old_values || $log->new_values)
                                    <button @click="expanded[{{ $i }}] = !expanded[{{ $i }}]"
                                        class="text-muted-foreground hover:text-foreground transition-colors">
                                        <svg class="size-4 transition-transform" :class="expanded[{{ $i }}] ? 'rotate-180' : ''"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        {{-- Diff row --}}
                        @if ($log->old_values || $log->new_values)
                            <tr x-show="expanded[{{ $i }}]" style="display: none"
                                class="bg-muted/20 border-b border-border/30">
                                <td colspan="6" class="px-6 py-3">
                                    <div class="grid grid-cols-2 gap-4 text-xs font-mono">
                                        @if ($log->old_values)
                                            <div>
                                                <p class="text-muted-foreground mb-1 font-sans font-medium">Before</p>
                                                <pre class="rounded bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 p-2 text-red-700 dark:text-red-400 overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        @endif
                                        @if ($log->new_values)
                                            <div>
                                                <p class="text-muted-foreground mb-1 font-sans font-medium">After</p>
                                                <pre class="rounded bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 p-2 text-emerald-700 dark:text-emerald-400 overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                {{ $search || $filterAction ? 'No logs match your filters.' : 'No audit logs yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-4 py-3 border-t border-border text-sm text-muted-foreground">
            <span>Showing {{ $this->logs->firstItem() ?? 0 }}–{{ $this->logs->lastItem() ?? 0 }} of {{ $this->logs->total() }} entries</span>
            @if ($this->logs->hasPages())
                {{ $this->logs->onEachSide(1)->links() }}
            @endif
        </div>

    </x-ui.card>
</div>
