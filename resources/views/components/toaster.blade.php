{{--
    Avisos flotantes (toasts), único por página, en la esquina superior derecha.

    Toma session('status') / session('error') al renderizar y también escucha el
    evento window `toast` -> add({ variant: 'success'|'danger', message, timeout }).
    Los de éxito se autodescartan; los de error se quedan hasta que el usuario los
    cierra. Reemplaza a los <x-alert> de flash que empujaban el contenido.
--}}
<div
    x-data="{
        toasts: [],
        add(toast) {
            if (! toast.message) return;
            const id = Date.now() + Math.random();
            const variant = toast.variant === 'danger' ? 'danger' : 'success';
            const timeout = toast.timeout ?? (variant === 'danger' ? 0 : 4500);
            this.toasts.push({ id, variant, message: toast.message, show: true });
            if (timeout > 0) {
                setTimeout(() => this.dismiss(id), timeout);
            }
        },
        dismiss(id) {
            const t = this.toasts.find((x) => x.id === id);
            if (! t) return;
            t.show = false;
            setTimeout(() => { this.toasts = this.toasts.filter((x) => x.id !== id); }, 200);
        },
    }"
    x-init="
        @if (session('status')) add({ variant: 'success', message: @js(session('status')) }); @endif
        @if (session('error')) add({ variant: 'danger', message: @js(session('error')) }); @endif
    "
    x-on:toast.window="add($event.detail || {})"
    class="pointer-events-none fixed inset-x-0 top-0 z-[60] flex flex-col items-center gap-2 p-4 sm:inset-x-auto sm:right-0 sm:items-end"
    x-cloak
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-[-8px] sm:translate-y-0 sm:translate-x-4"
            x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0 sm:translate-x-4"
            role="status"
            aria-live="polite"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-surface px-4 py-3 text-sm shadow-lg"
            x-bind:class="toast.variant === 'danger' ? 'border-danger-200 text-danger-700' : 'border-success-200 text-success-700'"
        >
            <span class="mt-px shrink-0">
                <x-icon name="exito" class="size-4" x-show="toast.variant !== 'danger'" />
                <x-icon name="error" class="size-4" x-show="toast.variant === 'danger'" x-cloak />
            </span>
            <p class="min-w-0 flex-1 text-ink" x-text="toast.message"></p>
            <button type="button" x-on:click="dismiss(toast.id)"
                    class="-m-1 shrink-0 rounded p-1 text-ink-faint transition-colors hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
                    aria-label="{{ __('Cerrar aviso') }}">
                <x-icon name="cerrar" class="size-4" />
            </button>
        </div>
    </template>
</div>
