@props([
    'label'       => null,
    'error'       => null,
    'description' => null,
    'id'          => null,
])

@php $inputId = $id ?? 'input-' . uniqid(); @endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium leading-none text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => 'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50' . ($error ? ' border-destructive focus-visible:ring-destructive' : '')]) }}
    />

    @if ($error)
        <p class="text-xs text-destructive">{{ $error }}</p>
    @elseif ($description)
        <p class="text-xs text-muted-foreground">{{ $description }}</p>
    @endif
</div>
