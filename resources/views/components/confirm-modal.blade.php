{{--
    Diálogo de confirmación tematizado, único por página.

    Intercepta el envío de cualquier <form data-confirm="..."> y pide confirmación
    antes de dejarlo pasar. Atributos admitidos en el form:
      data-confirm         (obligatorio) texto de la pregunta
      data-confirm-title   título del diálogo
      data-confirm-label   texto del botón de confirmar
      data-confirm-variant  danger (por defecto) | primary

    Reemplaza a onsubmit="return confirm(...)". La validación nativa del navegador
    (campos required, etc.) se respeta: si el form es inválido no se abre el diálogo.
--}}
<div
    x-data="{
        open: false,
        form: null,
        title: '',
        message: '',
        confirmText: '{{ __('Confirmar') }}',
        cancelText: '{{ __('Cancelar') }}',
        variant: 'danger',
        init() {
            document.addEventListener('submit', (e) => {
                const f = e.target;
                if (! (f instanceof HTMLFormElement) || f.dataset.confirm === undefined) return;
                if (f.dataset.confirmed === '1') { delete f.dataset.confirmed; return; }
                if (typeof f.checkValidity === 'function' && ! f.checkValidity()) return;
                e.preventDefault();
                this.form = f;
                this.title = f.dataset.confirmTitle || '{{ __('¿Confirmar acción?') }}';
                this.message = f.dataset.confirm;
                this.confirmText = f.dataset.confirmLabel || '{{ __('Confirmar') }}';
                this.variant = f.dataset.confirmVariant || 'danger';
                this.open = true;
                this.$nextTick(() => this.$refs.confirmBtn && this.$refs.confirmBtn.focus());
            }, true);
        },
        accept() {
            const f = this.form;
            this.open = false;
            this.form = null;
            if (! f) return;
            f.dataset.confirmed = '1';
            f.requestSubmit ? f.requestSubmit() : f.submit();
        },
        cancel() {
            this.open = false;
            this.form = null;
        },
    }"
    x-on:keydown.escape.window="open && cancel()"
    x-cloak
>
    <div x-show="open" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0" style="display: none;">
        <div x-show="open"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-ink/50" x-on:click="cancel()"></div>

        <div x-show="open"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative mx-auto mt-16 max-w-md overflow-hidden rounded-xl bg-surface shadow-xl"
             role="alertdialog" aria-modal="true" x-bind:aria-label="title">
            <div class="flex gap-4 p-5">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full"
                      x-bind:class="variant === 'danger' ? 'bg-danger-50 text-danger-600' : 'bg-primary-50 text-primary-600'">
                    <x-icon name="advertencia" class="size-5" x-show="variant === 'danger'" />
                    <x-icon name="info" class="size-5" x-show="variant !== 'danger'" x-cloak />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-semibold text-ink" x-text="title"></h2>
                    <p class="mt-1 text-sm text-ink-soft" x-text="message"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-line bg-surface-sunken/50 px-5 py-3">
                <button type="button" x-on:click="cancel()"
                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-ink-soft transition hover:bg-surface-sunken hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                        x-text="cancelText"></button>
                <button type="button" x-ref="confirmBtn" x-on:click="accept()"
                        class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-white shadow-xs transition active:scale-[.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas"
                        x-bind:class="variant === 'danger'
                            ? 'bg-danger-600 hover:bg-danger-700 active:bg-danger-800 focus-visible:ring-danger-300'
                            : 'bg-primary-600 hover:bg-primary-700 active:bg-primary-800 focus-visible:ring-primary-300'"
                        x-text="confirmText"></button>
            </div>
        </div>
    </div>
</div>
