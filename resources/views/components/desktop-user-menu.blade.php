<x-ui.dropdown align="start">
    <x-slot:trigger>
        <button
            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted transition-colors w-full"
            data-test="sidebar-menu-button"
        >
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold">
                {{ auth()->user()->initials() }}
            </span>
            <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-medium text-foreground">{{ auth()->user()->name }}</span>
                <span class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4" />
            </svg>
        </button>
    </x-slot:trigger>

    <div class="px-2 py-1.5">
        <div class="flex items-center gap-2">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold">
                {{ auth()->user()->initials() }}
            </span>
            <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-medium text-foreground">{{ auth()->user()->name }}</span>
                <span class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</span>
            </div>
        </div>
    </div>

    <div class="h-px bg-border my-1"></div>

    <x-ui.dropdown-item :href="route('profile.edit')" wire:navigate>
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Settings
    </x-ui.dropdown-item>

    <div class="h-px bg-border my-1"></div>

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <x-ui.dropdown-item variant="destructive" as="button" type="submit" class="w-full" data-test="logout-button">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Log out
        </x-ui.dropdown-item>
    </form>
</x-ui.dropdown>
