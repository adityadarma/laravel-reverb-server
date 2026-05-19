<?php

use App\Models\App;
use App\Models\ReverbMetric;
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
    public function appStats(): array
    {
        $service = app(ReverbMetricsService::class);

        return App::active()->get()
            ->map(fn ($app) => [
                'id'          => $app->id,
                'name'        => $app->name,
                'connections' => $service->connections($app),
                'channels'    => $service->channelsWithInfo($app),
                'history'     => ReverbMetric::lastMinutes($app->id, 60),
            ])
            ->sortByDesc('connections')
            ->values()
            ->all();
    }
}; ?>

<div class="space-y-6" wire:poll.3s>

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-foreground">Dashboard</h1>
            <p class="text-sm text-muted-foreground mt-0.5">Overview of your Reverb server</p>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Live · updates every 3s
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <x-ui.card>
            <div class="p-5 flex items-center gap-4">
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-2.5 shrink-0">
                    <svg class="size-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-foreground">{{ $this->totalApps }}</p>
                    <p class="text-xs text-muted-foreground">Apps <span class="text-emerald-500">· {{ $this->activeApps }} active</span></p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="p-5 flex items-center gap-4">
                <div class="rounded-md bg-emerald-50 dark:bg-emerald-900/20 p-2.5 shrink-0">
                    <svg class="size-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-foreground">{{ $this->totalConnections }}</p>
                    <p class="text-xs text-muted-foreground">Connections</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="p-5 flex items-center gap-4">
                <div class="rounded-md bg-violet-50 dark:bg-violet-900/20 p-2.5 shrink-0">
                    <svg class="size-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-foreground">
                        {{ collect($this->appStats)->sum(fn ($a) => count($a['channels'])) }}
                    </p>
                    <p class="text-xs text-muted-foreground">Active Channels</p>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Apps table --}}
    @if ($this->activeApps > 0)
        <x-ui.card>
            <div class="px-5 py-3.5 border-b border-border">
                <h2 class="text-sm font-semibold text-foreground">Apps Overview</h2>
                <p class="text-xs text-muted-foreground mt-0.5">Last 60 minutes · recorded every minute</p>
            </div>

            <div x-data="{ expanded: {} }">
                @foreach ($this->appStats as $i => $stat)
                    @php $hasChannels = count($stat['channels']) > 0; @endphp

                    {{-- App row --}}
                    <div
                        class="flex items-center gap-4 px-5 py-3 border-b border-border/50 {{ $hasChannels ? 'cursor-pointer hover:bg-muted/30' : '' }} transition-colors"
                        @if ($hasChannels) @click="expanded[{{ $i }}] = !expanded[{{ $i }}]" @endif
                    >
                        <span class="size-2 rounded-full shrink-0 {{ $stat['connections'] > 0 ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-foreground truncate">{{ $stat['name'] }}</p>
                            <p class="text-xs text-muted-foreground font-mono truncate">{{ Str::limit($stat['id'], 24) }}</p>
                        </div>

                        {{-- Sparkline --}}
                        @if (count($stat['history']) > 1)
                            @php
                                $points = collect($stat['history'])->pluck('connections')->all();
                                $max    = max(max($points), 1);
                                $w      = 80;
                                $h      = 24;
                                $step   = $w / (count($points) - 1);
                                $coords = collect($points)->map(fn ($v, $k) =>
                                    round($k * $step, 1) . ',' . round($h - ($v / $max) * $h, 1)
                                )->implode(' ');
                            @endphp
                            <svg width="{{ $w }}" height="{{ $h }}" class="shrink-0 text-emerald-500">
                                <polyline
                                    points="{{ $coords }}"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        @else
                            <div class="w-20 h-6 shrink-0"></div>
                        @endif

                        <div class="flex items-center gap-6 shrink-0">
                            <div class="text-center">
                                <p class="text-sm font-semibold text-foreground">{{ $stat['connections'] }}</p>
                                <p class="text-xs text-muted-foreground">conn</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-foreground">{{ count($stat['channels']) }}</p>
                                <p class="text-xs text-muted-foreground">ch</p>
                            </div>
                            <a href="{{ route('apps.show', $stat['id']) }}" wire:navigate @click.stop
                                class="text-xs text-muted-foreground hover:text-foreground transition-colors">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                            @if ($hasChannels)
                                <svg class="size-4 text-muted-foreground transition-transform" :class="expanded[{{ $i }}] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            @endif
                        </div>
                    </div>

                    {{-- Channels (collapsible) --}}
                    @if ($hasChannels)
                        <div x-show="expanded[{{ $i }}]" style="display: none" class="bg-muted/20">
                            <div class="max-h-48 overflow-y-auto scrollbar-hide">
                                @foreach ($stat['channels'] as $channelName => $channelData)
                                    <div class="flex items-center gap-3 px-10 py-2 border-b border-border/30 last:border-0">
                                        @if (str_starts_with($channelName, 'presence-'))
                                            <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">presence</span>
                                        @elseif (str_starts_with($channelName, 'private-'))
                                            <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">private</span>
                                        @else
                                            <span class="shrink-0 rounded px-1.5 py-0.5 text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">public</span>
                                        @endif
                                        <span class="text-xs font-mono text-foreground flex-1 truncate">{{ $channelName }}</span>
                                        <div class="flex items-center gap-3 text-xs text-muted-foreground shrink-0">
                                            @php
                                                $subs    = $channelData['subscription_count'] ?? null;
                                                $users   = $channelData['user_count'] ?? null;
                                                $occupied = $channelData['occupied'] ?? null;
                                            @endphp
                                            @if ($subs !== null)
                                                <span class="flex items-center gap-1" title="Subscribers">
                                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    {{ $subs }}
                                                </span>
                                            @elseif ($occupied !== null)
                                                <span class="flex items-center gap-1 {{ $occupied ? 'text-emerald-500' : 'text-zinc-500' }}">
                                                    <span class="size-1.5 rounded-full {{ $occupied ? 'bg-emerald-500' : 'bg-zinc-500' }}"></span>
                                                    {{ $occupied ? 'occupied' : 'empty' }}
                                                </span>
                                            @endif
                                            @if ($users !== null)
                                                <span class="flex items-center gap-1 text-violet-500" title="Users">
                                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    {{ $users }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </x-ui.card>
    @endif

</div>
