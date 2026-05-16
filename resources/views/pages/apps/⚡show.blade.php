<?php

use App\Models\App;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('App Detail')] class extends Component {
    public App $app;

    public function mount(string $id): void
    {
        $this->app = App::findOrFail($id);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6"
    x-data="appDebugConsole({
        key: '{{ $app->key }}',
        wsHost: '{{ config('reverb.servers.reverb.hostname', '127.0.0.1') }}',
        wsPort: {{ config('reverb.servers.reverb.port', 8080) }},
        forceTLS: {{ app()->isProduction() ? 'true' : 'false' }},
    })"
    x-init="connect()"
    @navigate-away.window="disconnect()"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('apps.index') }}" wire:navigate class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                    <flux:icon.arrow-left class="size-5" />
                </a>
                <flux:heading size="xl">{{ $app->name }}</flux:heading>
                <flux:badge :variant="$app->is_active ? 'success' : 'danger'" size="sm">
                    {{ $app->is_active ? 'Active' : 'Inactive' }}
                </flux:badge>
            </div>
            <flux:subheading class="ml-8">Debug Console</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 text-sm">
                <span class="size-2 rounded-full" :class="connected ? 'bg-green-500' : 'bg-red-500'"></span>
                <span class="text-zinc-500 dark:text-zinc-400" x-text="connected ? 'Connected' : 'Disconnected'"></span>
            </div>
            <flux:button size="sm" variant="filled" @click="clearEvents()">
                Clear
            </flux:button>
        </div>
    </div>

    {{-- App Info --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">App ID</p>
            <p class="font-mono text-sm text-zinc-900 dark:text-zinc-100 truncate" title="{{ $app->id }}">{{ $app->id }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Key</p>
            <p class="font-mono text-sm text-zinc-900 dark:text-zinc-100 truncate" title="{{ $app->key }}">{{ $app->key }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Secret</p>
            <p class="font-mono text-sm text-zinc-900 dark:text-zinc-100 truncate" title="{{ $app->secret }}">{{ $app->secret }}</p>
        </div>
    </div>

    {{-- Channel Subscribe --}}
    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Subscribe to Channel</label>
            <input
                type="text"
                x-model="channelInput"
                @keydown.enter="subscribeChannel()"
                placeholder="e.g. my-channel, presence-room, private-chat"
                class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
        </div>
        <button
            @click="subscribeChannel()"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors"
        >
            Subscribe
        </button>
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

    {{-- Event Log --}}
    <div class="flex-1 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-900 flex flex-col min-h-[400px]">
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
        this.events.push({
            type,
            label,
            channel,
            data,
            time: new Date().toLocaleTimeString(),
        });

        // Keep max 200 events
        if (this.events.length > 200) {
            this.events = this.events.slice(-200);
        }
    },
}));
</script>
@endscript
