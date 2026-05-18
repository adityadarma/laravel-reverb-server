@props(['variant' => 'default'])

@php
$variants = [
    'default'     => 'bg-primary text-primary-foreground',
    'secondary'   => 'bg-secondary text-secondary-foreground',
    'destructive' => 'bg-destructive text-destructive-foreground',
    'outline'     => 'border border-input text-foreground',
    'success'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'warning'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'danger'      => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'zinc'        => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {$variants[$variant]}"]) }}>
    {{ $slot }}
</span>
