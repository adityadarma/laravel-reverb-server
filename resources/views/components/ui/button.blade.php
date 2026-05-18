@props([
    'variant' => 'default',
    'size'    => 'default',
    'as'      => 'button',
    'type'    => 'button',
])

@php
$variants = [
    'default'     => 'bg-primary text-primary-foreground hover:bg-primary/90',
    'destructive' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    'outline'     => 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
    'secondary'   => 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
    'ghost'       => 'hover:bg-accent hover:text-accent-foreground',
    'link'        => 'text-primary underline-offset-4 hover:underline',
];

$sizes = [
    'default' => 'h-9 px-4 py-2 text-sm',
    'sm'      => 'h-8 px-3 text-xs',
    'lg'      => 'h-10 px-8 text-sm',
    'icon'    => 'h-9 w-9',
];

$base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50';
@endphp

@if ($as === 'a')
    <a {{ $attributes->merge(['class' => "$base {$variants[$variant]} {$sizes[$size]}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base {$variants[$variant]} {$sizes[$size]}"]) }}>
        {{ $slot }}
    </button>
@endif
