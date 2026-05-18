<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h2 class="text-base font-semibold text-foreground">Delete account</h2>
        <p class="text-sm text-muted-foreground mt-1">Delete your account and all of its resources</p>
    </div>

    <x-ui.button
        variant="destructive"
        data-test="delete-user-button"
        @click="$dispatch('open-modal-confirm-user-deletion')"
    >
        Delete account
    </x-ui.button>

    <livewire:pages::settings.delete-user-modal />
</section>
