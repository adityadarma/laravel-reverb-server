<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-background font-sans antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center p-4">
            <div class="w-full max-w-sm space-y-6">
                {{-- Logo --}}
                <div class="flex flex-col items-center gap-2">
                    <a href="{{ route('login') }}" class="flex items-center gap-2.5">
                        <div class="flex size-9 items-center justify-center rounded-md bg-foreground">
                            <x-app-logo-icon class="size-5 text-background" />
                        </div>
                    </a>
                    <h1 class="text-xl font-semibold text-foreground">{{ config('app.name') }}</h1>
                </div>

                {{-- Card --}}
                <x-ui.card>
                    <div class="p-6">
                        {{ $slot }}
                    </div>
                </x-ui.card>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
