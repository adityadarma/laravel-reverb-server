@props([
    'label'       => null,
    'error'       => null,
    'description' => null,
    'id'          => null,
])

@php $inputId = $id ?? 'select-' . uniqid(); @endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium leading-none text-foreground">
            {{ $label }}
        </label>
    @endif

    <select
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50']) }}
    >
        {{ $slot }}
    </select>

    @if ($error)
        <p class="text-xs text-destructive">{{ $error }}</p>
    @elseif ($description)
        <p class="text-xs text-muted-foreground">{{ $description }}</p>
    @endif
</div>
