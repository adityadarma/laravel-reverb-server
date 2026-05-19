<?php

use App\Models\App;
use App\Models\AuditLog;
use App\Models\ReverbMetric;
use App\Services\ReverbMetricsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('App Detail')] class extends Component {
    public App $app;

    // Live stats — updated via refreshLiveStats() called from Alpine interval
    public int $liveConnections = 0;
    public int $liveChannels    = 0;

    // Send event form
    public string $sendChannel = '';
    public string $sendEvent   = '';
    public string $sendData    = '{}';

    public function mount(string $id): void
    {
        $this->app = App::findOrFail($id);
        $this->refreshLiveStats();
    }

    #[Computed(persist: false)]
    public function metrics(): array
    {
        return ReverbMetric::lastMinutes($this->app->id, 60);
    }

    public function refreshLiveStats(): void
    {
        $service = app(ReverbMetricsService::class);
        $this->liveConnections = $service->connections($this->app);
        $this->liveChannels    = count($service->channels($this->app));
    }

    public function rotateKey(): void
    {
        $old = $this->app->key;
        $this->app->update(['key' => Str::random(20)]);

        AuditLog::record('rotated_key', $this->app, ['key' => $old], ['key' => $this->app->key]);

        $this->dispatch('toast', message: 'App key rotated successfully.');
    }

    public function rotateSecret(): void
    {
        $old = $this->app->secret;
        $this->app->update(['secret' => Str::random(40)]);

        AuditLog::record('rotated_secret', $this->app, ['secret' => '***'], ['secret' => '***']);

        $this->dispatch('toast', message: 'App secret rotated successfully.');
    }

    public function dispatchEvent(string $channel, string $event, string $data): void
    {
        if (empty($channel) || empty($event)) {
            $this->addError('sendData', 'Channel and event name are required.');
            return;
        }

        $decoded = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError('sendData', 'Invalid JSON: ' . json_last_error_msg());
            return;
        }

        $host = config('reverb.servers.reverb.host', '0.0.0.0');
        $port = config('reverb.servers.reverb.port', 8080);
        $path = config('reverb.servers.reverb.path', '');

        if ($host === '0.0.0.0') {
            $host = '127.0.0.1';
        }

        $scheme   = app()->isProduction() ? 'https' : 'http';
        $endpoint = "/apps/{$this->app->id}/events";

        $body = json_encode([
            'name'     => $event,
            'channels' => [$channel],
            'data'     => json_encode($decoded),
        ]);

        $timestamp = (string) time();
        $bodyMd5   = md5($body);

        $params = [
            'auth_key'       => $this->app->key,
            'auth_timestamp' => $timestamp,
            'auth_version'   => '1.0',
            'body_md5'       => $bodyMd5,
        ];
        ksort($params);

        $queryString = http_build_query($params);
        $toSign      = "POST\n{$endpoint}\n{$queryString}";
        $signature   = hash_hmac('sha256', $toSign, $this->app->secret);

        $url = "{$scheme}://{$host}:{$port}{$path}{$endpoint}?{$queryString}&auth_signature={$signature}";

        try {
            $response = Http::timeout(3)
                ->withBody($body, 'application/json')
                ->post($url);

            if ($response->successful()) {
                $this->dispatch('event-sent');
            } else {
                $this->addError('sendData', 'Failed: ' . $response->body());
            }
        } catch (\Throwable $e) {
            $this->addError('sendData', 'Error: ' . $e->getMessage());
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6"
    x-data="appDebugConsole({
        key: '{{ $app->key }}',
        wsHost: '{{ config('reverb.servers.reverb.hostname', '127.0.0.1') }}',
        wsPort: {{ config('reverb.servers.reverb.port', 8080) }},
        forceTLS: {{ app()->isProduction() ? 'true' : 'false' }},
    })"
    x-init="connect(); startStatsPolling()"
    @navigate-away.window="disconnect(); stopStatsPolling()"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('apps.index') }}" wire:navigate class="text-muted-foreground hover:text-foreground transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-foreground">{{ $app->name }}</h1>
                <x-ui.badge :variant="$app->is_active ? 'success' : 'danger'">
                    {{ $app->is_active ? 'Active' : 'Inactive' }}
                </x-ui.badge>
            </div>
            <p class="text-sm text-muted-foreground mt-1 ml-8">App Detail</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 text-sm">
                <span class="size-2 rounded-full" :class="connected ? 'bg-green-500' : 'bg-red-500'"></span>
                <span class="text-muted-foreground" x-text="connected ? 'Connected' : 'Disconnected'"></span>
            </div>
            <x-ui.button size="sm" variant="outline" @click="clearEvents()">Clear</x-ui.button>
        </div>
    </div>

    {{-- App Credentials --}}
    <x-ui.card>
        <div class="px-5 py-3.5 border-b border-border flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-foreground">Credentials</h2>
                <p class="text-xs text-muted-foreground mt-0.5">Keep your secret safe — rotate if compromised</p>
            </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-border">
            {{-- App ID --}}
            <div class="p-5" x-data="{ copied: false }">
                <p class="text-xs text-muted-foreground mb-2">App ID</p>
                <div class="flex items-center gap-2">
                    <p class="font-mono text-sm text-foreground truncate flex-1" title="{{ $app->id }}">{{ $app->id }}</p>
                    <button @click="navigator.clipboard.writeText('{{ $app->id }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="shrink-0 text-muted-foreground hover:text-foreground transition-colors" title="Copy">
                        <svg x-show="!copied" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copied" class="size-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </div>
            {{-- Key --}}
            <div class="p-5" x-data="{ copied: false }">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted-foreground">Key</p>
                    <button wire:click="rotateKey" wire:confirm="Rotate the app key? All clients using the current key will be disconnected."
                        class="text-xs text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1 transition-colors">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Rotate
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <p class="font-mono text-sm text-foreground truncate flex-1" title="{{ $app->key }}">{{ $app->key }}</p>
                    <button @click="navigator.clipboard.writeText('{{ $app->key }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="shrink-0 text-muted-foreground hover:text-foreground transition-colors" title="Copy">
                        <svg x-show="!copied" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copied" class="size-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </div>
            {{-- Secret --}}
            <div class="p-5" x-data="{ copied: false, show: false }">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs text-muted-foreground">Secret</p>
                    <button wire:click="rotateSecret" wire:confirm="Rotate the app secret? Update your server-side integrations immediately."
                        class="text-xs text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1 transition-colors">
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Rotate
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <p class="font-mono text-sm text-foreground truncate flex-1" x-text="show ? '{{ $app->secret }}' : '••••••••••••••••••••'"></p>
                    <button @click="show = !show" class="shrink-0 text-muted-foreground hover:text-foreground transition-colors" :title="show ? 'Hide' : 'Show'">
                        <svg x-show="!show" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                    <button @click="navigator.clipboard.writeText('{{ $app->secret }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="shrink-0 text-muted-foreground hover:text-foreground transition-colors" title="Copy">
                        <svg x-show="!copied" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copied" class="size-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Metrics Chart --}}
    @php
        $metrics    = $this->metrics;
        $hasMetrics = count($metrics) > 1;
        $connPoints = collect($metrics)->pluck('connections')->all();
        $chPoints   = collect($metrics)->pluck('channels')->all();
        $maxConn    = max(array_merge($connPoints, [1]));
        $maxCh      = max(array_merge($chPoints, [1]));
        $w = 100; $h = 48;
        $step = $hasMetrics ? $w / (count($metrics) - 1) : $w;
        $connCoords = collect($connPoints)->map(fn($v, $k) =>
            round($k * $step, 1) . ',' . round($h - ($v / $maxConn) * $h, 1)
        )->implode(' ');
        $chCoords = collect($chPoints)->map(fn($v, $k) =>
            round($k * $step, 1) . ',' . round($h - ($v / $maxCh) * $h, 1)
        )->implode(' ');
    @endphp

    <div class="grid grid-cols-2 gap-4">
        {{-- Connections chart --}}
        <x-ui.card>
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Live Connections</p>
                        <p class="text-2xl font-bold text-foreground">{{ $liveConnections }}</p>
                    </div>
                    <span class="text-xs text-muted-foreground">Last 60 min</span>
                </div>
                @if ($hasMetrics)
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-12 text-emerald-500" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="connGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="currentColor" stop-opacity="0.2"/>
                                <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <polygon points="{{ $connCoords }} {{ $w }},{{ $h }} 0,{{ $h }}" fill="url(#connGrad)"/>
                        <polyline points="{{ $connCoords }}" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @else
                    <div class="h-12 flex items-center justify-center text-xs text-muted-foreground">No history yet</div>
                @endif
            </div>
        </x-ui.card>

        {{-- Channels chart --}}
        <x-ui.card>
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Active Channels</p>
                        <p class="text-2xl font-bold text-foreground">{{ $liveChannels }}</p>
                    </div>
                    <span class="text-xs text-muted-foreground">Last 60 min</span>
                </div>
                @if ($hasMetrics)
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full h-12 text-violet-500" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="chGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="currentColor" stop-opacity="0.2"/>
                                <stop offset="100%" stop-color="currentColor" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <polygon points="{{ $chCoords }} {{ $w }},{{ $h }} 0,{{ $h }}" fill="url(#chGrad)"/>
                        <polyline points="{{ $chCoords }}" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @else
                    <div class="h-12 flex items-center justify-center text-xs text-muted-foreground">No history yet</div>
                @endif
            </div>
        </x-ui.card>
    </div>


    {{-- Debug Console --}}
    <div class="flex items-center gap-2">
        <h2 class="text-base font-semibold text-foreground">Debug Console</h2>
        <span class="text-xs text-muted-foreground">· Subscribe to channels and send events</span>
    </div>

    {{-- Channel Subscribe --}}
    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium text-foreground mb-1">Subscribe to Channel</label>
            <x-ui.input
                x-model="channelInput"
                @keydown.enter="subscribeChannel()"
                placeholder="e.g. my-channel, presence-room, private-chat"
            />
        </div>
        <x-ui.button @click="subscribeChannel()">Subscribe</x-ui.button>
    </div>

    {{-- Active Channels --}}
    <div x-show="channels.length > 0" class="flex flex-wrap gap-2">
        <template x-for="ch in channels" :key="ch">
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-900/30 px-3 py-1 text-xs font-medium text-blue-700 dark:text-blue-300">
                <span x-text="ch"></span>
                <button @click="unsubscribeChannel(ch)" class="hover:text-red-500 transition-colors">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </span>
        </template>
    </div>

    {{-- Send Event --}}
    <div x-show="channels.length > 0" class="rounded-lg border border-border bg-card p-4 space-y-3">
        <p class="text-sm font-medium text-foreground">Send Event</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="block text-xs text-muted-foreground mb-1">Channel</label>
                <x-ui.select x-model="sendChannel" @change="$wire.set('sendChannel', sendChannel)">
                    <option value="">Select channel...</option>
                    <template x-for="ch in channels" :key="ch">
                        <option :value="ch" x-text="ch"></option>
                    </template>
                </x-ui.select>
            </div>
            <div>
                <label class="block text-xs text-muted-foreground mb-1">Event Name</label>
                <x-ui.input x-model="sendEventName" placeholder="e.g. my-event"/>
            </div>
            <div class="flex items-end">
                <x-ui.button class="w-full" @click="$wire.dispatchEvent(sendChannel, sendEventName, sendData)">Send</x-ui.button>
            </div>
        </div>
        <div>
            <label class="block text-xs text-muted-foreground mb-1">Data (JSON)</label>
            <x-ui.textarea x-model="sendData" rows="3" placeholder='{"message": "hello"}' class="font-mono"></x-ui.textarea>
            @error('sendData')
                <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Event Log --}}
    <div class="flex-1 overflow-hidden rounded-lg border border-border bg-zinc-900 flex flex-col min-h-[400px]">
        <div class="flex items-center justify-between px-4 py-2 border-b border-zinc-700 bg-zinc-800">
            <span class="text-xs font-medium text-zinc-400">Event Log</span>
            <span class="text-xs text-zinc-500" x-text="events.length + ' events'"></span>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-2 font-mono text-xs" x-ref="eventLog">
            <template x-for="(event, idx) in events" :key="idx">
                <div class="rounded-lg border border-zinc-700 bg-zinc-800 p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold" :class="{
                            'text-green-400': event.type === 'event',
                            'text-blue-400': event.type === 'subscribed',
                            'text-yellow-400': event.type === 'connection',
                            'text-red-400': event.type === 'error',
                        }" x-text="event.label"></span>
                        <span class="text-zinc-500" x-text="event.time"></span>
                    </div>
                    <div x-show="event.channel" class="text-zinc-400 mb-1">
                        Channel: <span class="text-zinc-200" x-text="event.channel"></span>
                    </div>
                    <div x-show="event.data" class="text-zinc-300 whitespace-pre-wrap break-all" x-text="event.data"></div>
                </div>
            </template>
            <div x-show="events.length === 0" class="flex items-center justify-center h-full text-zinc-500">
                Waiting for events...
            </div>
        </div>
    </div>

</div>


@script
<script>
Alpine.data('appDebugConsole', (config) => ({
    connected: false,
    pusher: null,
    channels: [],
    events: [],
    channelInput: '',
    sendChannel: '',
    sendEventName: '',
    sendData: '{}',
    _statsInterval: null,

    startStatsPolling() {
        this._statsInterval = setInterval(() => {
            this.$wire.refreshLiveStats();
        }, 3000);
    },

    stopStatsPolling() {
        if (this._statsInterval) {
            clearInterval(this._statsInterval);
            this._statsInterval = null;
        }
    },

    connect() {
        if (!window.Pusher) {
            this.addEvent('error', 'Error', null, 'Pusher JS not loaded');
            return;
        }

        this.pusher = new window.Pusher(config.key, {
            wsHost: config.wsHost || '127.0.0.1',
            wsPort: config.wsPort || 8080,
            wssPort: config.wsPort || 443,
            forceTLS: config.forceTLS,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            cluster: '',
        });

        this.pusher.connection.bind('connected', () => {
            this.connected = true;
            this.addEvent('connection', 'Connected', null, 'Connection established');
        });

        this.pusher.connection.bind('disconnected', () => {
            this.connected = false;
            this.addEvent('error', 'Disconnected', null, 'Connection lost');
        });

        this.pusher.connection.bind('error', (err) => {
            this.addEvent('error', 'Connection Error', null, err?.error?.data?.message || JSON.stringify(err, null, 2));
        });

        this.pusher.connection.bind('state_change', (states) => {
            if (states.current === 'unavailable' || states.current === 'failed') {
                this.connected = false;
                this.addEvent('error', 'Connection ' + states.current, null, 'Reverb server may not be running on ' + config.wsHost + ':' + config.wsPort);
            }
        });
    },

    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
            this.pusher = null;
            this.connected = false;
            this.channels = [];
        }
    },

    subscribeChannel() {
        const name = this.channelInput.trim();
        if (!name || this.channels.includes(name)) return;

        const channel = this.pusher.subscribe(name);

        channel.bind_global((eventName, data) => {
            if (eventName.startsWith('pusher:')) {
                if (eventName === 'pusher:subscription_succeeded') {
                    this.addEvent('subscribed', `Subscribed: ${name}`, name, null);
                }
                return;
            }
            this.addEvent('event', eventName, name, JSON.stringify(data, null, 2));
            this.$nextTick(() => {
                if (this.$refs.eventLog) {
                    this.$refs.eventLog.scrollTop = this.$refs.eventLog.scrollHeight;
                }
            });
        });

        this.channels.push(name);
        this.channelInput = '';
        if (this.channels.length === 1) {
            this.sendChannel = name;
            this.$wire.set('sendChannel', name);
        }
    },

    unsubscribeChannel(name) {
        this.pusher.unsubscribe(name);
        this.channels = this.channels.filter(ch => ch !== name);
        this.addEvent('subscribed', `Unsubscribed: ${name}`, name, null);
    },

    clearEvents() {
        this.events = [];
    },

    addEvent(type, label, channel, data) {
        this.events.push({ type, label, channel, data, time: new Date().toLocaleTimeString() });
        if (this.events.length > 200) {
            this.events = this.events.slice(-200);
        }
    },
}));
</script>
@endscript
