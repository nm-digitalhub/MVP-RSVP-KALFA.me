@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'focusable' => true,
])

@php
$maxWidthClasses = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        previouslyFocused: null,
        focusFirst() {
            this.$nextTick(() => {
                const focusableElements = this.$el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex=\'-1\'])');
                if (focusableElements.length > 0) {
                    focusableElements[0].focus();
                }
            });
        },
        restoreFocus() {
            if (this.previouslyFocused) {
                this.previouslyFocused.focus();
                this.previouslyFocused = null;
            }
        }
    }"
    x-on:open-modal.window="
        if ($event.detail === '{{ $name }}') {
            previouslyFocused = document.activeElement;
            show = true;
            $nextTick(() => focusFirst());
        }
    "
    x-on:close.window="
        show = false;
        $nextTick(() => restoreFocus());
    "
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto hidden"
    role="dialog"
    aria-modal="true"
    aria-hidden="{{ $show ? 'true' : 'false' }}"
    @if($show) aria-labelledby="modal-{{ $name }}-title" @endif
>
    <div x-show="show" class="fixed inset-0 bg-gray-500/75 transition-opacity" x-on:click="show = false" aria-hidden="true"></div>
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-lg bg-white text-start shadow-xl transition-all sm:my-8 sm:w-full {{ $maxWidthClasses }}"
            x-on:click.stop
        >
            {{ $slot }}
        </div>
    </div>
</div>

<script>
document.addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
        const modal = document.querySelector('[aria-modal="true"]');
        if (modal) {
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusableElements.length > 0) {
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    }
});
</script>
