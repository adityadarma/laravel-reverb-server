@props([
    'name'  => 'modal',
    'maxWidth' => 'lg',
])

@php
$sizes = [
    'sm'  => 'max-w-sm',
    'md'  => 'max-w-md',
    'lg'  => 'max-w-lg',
    'xl'  => 'max-w-xl',
    '2xl' => 'max-w-2xl',
];
$size = $sizes[$maxWidth] ?? 'max-w-lg';
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal-{{ $name }}.window="open = true"
    x-on:close-modal-{{ $name }}.window="open = false"
    x-show="open"
    style="display: none"
    class="fixed inset-0 z-50 flex items-center justify-center"
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
        @click="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Panel --}}
    <div
        class="relative z-10 w-full {{ $size }} mx-4 rounded-lg border border-border bg-card shadow-lg"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
    >
        <div class="p-6">
            {{ $slot }}
        </div>
    </div>
</div>
