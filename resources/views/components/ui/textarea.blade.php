@props([
    'label'       => null,
    'error'       => null,
    'description' => null,
    'id'          => null,
])

@php $inputId = $id ?? 'textarea-' . uniqid(); @endphp

<div class="space-y-1.5">
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium leading-none text-foreground">
            {{ $label }}
        </label>
    @endif

    <textarea
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => 'flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50' . ($error ? ' border-destructive' : '')]) }}
    >{{ $slot }}</textarea>

    @if ($error)
        <p class="text-xs text-destructive">{{ $error }}</p>
    @elseif ($description)
        <p class="text-xs text-muted-foreground">{{ $description }}</p>
    @endif
</div>
