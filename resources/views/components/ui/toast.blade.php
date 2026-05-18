<div
    x-data="toastManager()"
    x-on:toast.window="add($event.detail)"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-80"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="flex items-start gap-3 rounded-lg border border-border bg-card p-4 shadow-lg"
        >
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-card-foreground" x-text="toast.title" x-show="toast.title"></p>
                <p class="text-sm text-muted-foreground" x-text="toast.message"></p>
            </div>
            <button @click="remove(toast.id)" class="text-muted-foreground hover:text-foreground transition-colors shrink-0">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('toastManager', () => ({
        toasts: [],
        add(detail) {
            const id = Date.now();
            this.toasts.push({ id, visible: true, ...detail });
            setTimeout(() => this.remove(id), detail.duration ?? 4000);
        },
        remove(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 300);
        },
    }));
});
</script>
