@props([
    'label' => null,
    'id'    => null,
])

@php $inputId = $id ?? 'checkbox-' . uniqid(); @endphp

<div class="flex items-center gap-2">
    <input
        type="checkbox"
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => 'h-4 w-4 rounded border border-input bg-background text-primary focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50']) }}
    />
    @if ($label)
        <label for="{{ $inputId }}" class="text-sm font-medium leading-none text-foreground cursor-pointer">
            {{ $label }}
        </label>
    @endif
</div>
