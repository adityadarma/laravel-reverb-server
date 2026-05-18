@props([
    'variant' => 'default',
    'href'    => null,
    'icon'    => null,
])

@php
$base = 'flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors cursor-pointer';
$variants = [
    'default'     => 'text-foreground hover:bg-accent hover:text-accent-foreground',
    'destructive' => 'text-destructive hover:bg-destructive/10',
];
$class = "$base {$variants[$variant]}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        @if ($icon)
            <x-dynamic-component :component="'ui.icon.' . $icon" class="size-4" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $class]) }}>
        @if ($icon)
            <x-dynamic-component :component="'ui.icon.' . $icon" class="size-4" />
        @endif
        {{ $slot }}
    </button>
@endif
