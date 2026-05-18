@props(['align' => 'end'])

@php
$alignClass = $align === 'start' ? 'left-0' : 'right-0';
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <div @click="open = !open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="open = false"
        class="absolute {{ $alignClass }} top-full mt-1 z-50 min-w-[8rem] rounded-md border border-border bg-popover p-1 shadow-md"
        style="display: none"
    >
        {{ $slot }}
    </div>
</div>
