@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h2 class="text-2xl font-semibold text-foreground">{{ $title }}</h2>
    <p class="text-sm text-muted-foreground">{{ $description }}</p>
</div>
