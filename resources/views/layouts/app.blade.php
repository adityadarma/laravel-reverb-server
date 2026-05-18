<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background font-sans antialiased">
        <div class="flex min-h-screen" x-data="{ sidebarOpen: false }">

            {{-- Sidebar --}}
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-sidebar-border bg-sidebar transition-transform duration-200 lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                {{-- Logo --}}
                <div class="flex h-14 items-center border-b border-sidebar-border px-4">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
                        <div class="flex size-7 items-center justify-center rounded-md bg-foreground">
                            <x-app-logo-icon class="size-4 text-background" />
                        </div>
                        <span class="font-semibold text-sidebar-foreground">{{ config('app.name') }}</span>
                    </a>
                </div>

                {{-- Navigation --}}
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                    <p class="px-2 mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Main Menu</p>

                    <a href="{{ route('dashboard') }}" wire:navigate
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Dashboard
                    </a>

                    <a href="{{ route('apps.index') }}" wire:navigate
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('apps.*') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Apps
                    </a>
                </nav>

                {{-- User menu --}}
                <div class="border-t border-sidebar-border p-3">
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button
                            @click="open = !open"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-sidebar-accent transition-colors"
                        >
                            <div class="flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold shrink-0">
                                {{ auth()->user()->initials() }}
                            </div>
                            <div class="flex-1 text-left min-w-0">
                                <p class="text-sm font-medium text-sidebar-foreground truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <svg class="size-4 text-muted-foreground shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                            </svg>
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
                            class="absolute left-0 bottom-full mb-1 z-50 w-56 rounded-md border border-border bg-card p-1 shadow-md"
                            style="display: none"
                        >
                            <div class="px-2 py-1.5 mb-1">
                                <p class="text-xs font-medium text-foreground">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="h-px bg-border my-1"></div>
                            <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-foreground hover:bg-accent transition-colors cursor-pointer">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Settings
                            </a>
                            <div class="h-px bg-border my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm text-destructive hover:bg-destructive/10 transition-colors cursor-pointer">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Mobile overlay --}}
            <div
                class="fixed inset-0 z-30 bg-black/50 lg:hidden"
                x-show="sidebarOpen"
                @click="sidebarOpen = false"
                style="display: none"
            ></div>

            {{-- Main content --}}
            <div class="flex flex-1 flex-col lg:pl-64">
                {{-- Mobile header --}}
                <header class="flex h-14 items-center gap-4 border-b border-border bg-background px-4 lg:hidden">
                    <button @click="sidebarOpen = true" class="text-muted-foreground hover:text-foreground transition-colors">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="font-semibold text-foreground">{{ config('app.name') }}</span>
                </header>

                {{-- Page content --}}
                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Toast --}}
        <x-ui.toast />

        @livewireScripts
    </body>
</html>
