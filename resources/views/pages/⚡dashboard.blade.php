<?php

use App\Models\App;
use App\Services\ReverbMetricsService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {

    #[Computed(persist: false)]
    public function totalApps(): int
    {
        return App::count();
    }

    #[Computed(persist: false)]
    public function activeApps(): int
    {
        return App::active()->count();
    }

    #[Computed(persist: false)]
    public function totalConnections(): int
    {
        return app(ReverbMetricsService::class)->totalConnections();
    }

    #[Computed(persist: false)]
    public function appConnections(): array
    {
        $service = app(ReverbMetricsService::class);

        return App::active()->get()
            ->map(fn ($app) => [
                'name'        => $app->name,
                'connections' => $service->connections($app),
            ])
            ->sortByDesc('connections')
            ->values()
            ->all();
    }
}; ?>

<div class="space-y-6" wire:poll.10s>

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-semibold text-foreground">Dashboard</h1>
        <p class="text-sm text-muted-foreground mt-1">Overview of your Reverb server</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted-foreground">Total Apps</p>
                    <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-2">
                        <svg class="size-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-foreground">{{ $this->totalApps }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ $this->activeApps }} active</p>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted-foreground">Active Connections</p>
                    <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/20 p-2">
                        <svg class="size-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-foreground">{{ $this->totalConnections }}</p>
                <p class="mt-1 text-xs text-muted-foreground">across all active apps</p>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-muted-foreground">Inactive Apps</p>
                    <div class="rounded-md bg-zinc-100 dark:bg-zinc-800 p-2">
                        <svg class="size-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-3xl font-bold text-foreground">{{ $this->totalApps - $this->activeApps }}</p>
                <p class="mt-1 text-xs text-muted-foreground">disabled apps</p>
            </div>
        </x-ui.card>

    </div>

    {{-- Per-app connections --}}
    @if ($this->activeApps > 0)
        <x-ui.card>
            <div class="p-6 border-b border-border">
                <h2 class="text-base font-semibold text-foreground">Connections per App</h2>
                <p class="text-sm text-muted-foreground mt-0.5">Live WebSocket connections from Reverb server</p>
            </div>
            <div class="divide-y divide-border">
                @foreach ($this->appConnections as $item)
                    <div class="flex items-center justify-between px-6 py-3">
                        <span class="text-sm font-medium text-foreground">{{ $item['name'] }}</span>
                        <div class="flex items-center gap-3">
                            <div class="h-1.5 rounded-full bg-emerald-500"
                                style="width: {{ $this->totalConnections > 0 ? max(8, ($item['connections'] / max($this->totalConnections, 1)) * 100) : 8 }}px">
                            </div>
                            <x-ui.badge variant="{{ $item['connections'] > 0 ? 'success' : 'zinc' }}">
                                {{ $item['connections'] }} connections
                            </x-ui.badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @endif

</div>
