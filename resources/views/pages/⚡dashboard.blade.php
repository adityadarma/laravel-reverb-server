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

<div class="flex h-full w-full flex-1 flex-col gap-6" wire:poll.10s>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Total Apps --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Apps</p>
                    <p class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $this->totalApps }}
                    </p>
                </div>
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/30 p-3">
                    <flux:icon.squares-2x2 class="size-6 text-blue-500" />
                </div>
            </div>
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $this->activeApps }} active
            </p>
        </div>

        {{-- Active Connections --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Active Connections</p>
                    <p class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $this->totalConnections }}
                    </p>
                </div>
                <div class="rounded-lg bg-green-50 dark:bg-green-900/30 p-3">
                    <flux:icon.signal class="size-6 text-green-500" />
                </div>
            </div>
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                across all active apps
            </p>
        </div>

        {{-- Inactive Apps --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Inactive Apps</p>
                    <p class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $this->totalApps - $this->activeApps }}
                    </p>
                </div>
                <div class="rounded-lg bg-zinc-100 dark:bg-zinc-700 p-3">
                    <flux:icon.x-circle class="size-6 text-zinc-400" />
                </div>
            </div>
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                disabled apps
            </p>
        </div>

    </div>

    {{-- Per-app connections --}}
    @if ($this->activeApps > 0)
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="lg">Connections per App</flux:heading>
                <flux:subheading>Live WebSocket connections from Reverb server</flux:subheading>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->appConnections as $item)
                    <div class="flex items-center justify-between px-5 py-3">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $item['name'] }}
                        </span>
                        <div class="flex items-center gap-2">
                            <div class="h-2 rounded-full bg-green-500 transition-all"
                                style="width: {{ $this->totalConnections > 0 ? max(4, ($item['connections'] / max($this->totalConnections, 1)) * 120) : 4 }}px">
                            </div>
                            <flux:badge variant="{{ $item['connections'] > 0 ? 'success' : 'zinc' }}" size="sm">
                                {{ $item['connections'] }} connections
                            </flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
