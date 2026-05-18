<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav aria-label="Settings" class="flex flex-col gap-1">
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                @class([
                    'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    'bg-muted text-foreground' => request()->routeIs('profile.edit'),
                    'text-muted-foreground hover:bg-muted hover:text-foreground' => !request()->routeIs('profile.edit'),
                ])
            >
                Profile
            </a>
            <a
                href="{{ route('security.edit') }}"
                wire:navigate
                @class([
                    'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    'bg-muted text-foreground' => request()->routeIs('security.edit'),
                    'text-muted-foreground hover:bg-muted hover:text-foreground' => !request()->routeIs('security.edit'),
                ])
            >
                Security
            </a>
            <a
                href="{{ route('appearance.edit') }}"
                wire:navigate
                @class([
                    'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    'bg-muted text-foreground' => request()->routeIs('appearance.edit'),
                    'text-muted-foreground hover:bg-muted hover:text-foreground' => !request()->routeIs('appearance.edit'),
                ])
            >
                Appearance
            </a>
        </nav>
    </div>

    <div class="h-px bg-border my-1 md:hidden w-full"></div>

    <div class="flex-1 self-stretch max-md:pt-6">
        <h2 class="text-base font-semibold text-foreground">{{ $heading ?? '' }}</h2>
        <p class="text-sm text-muted-foreground mt-1">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
