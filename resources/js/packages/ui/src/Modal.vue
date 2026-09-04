<script setup lang="ts">
import { Dialog, DialogContent, DialogFooter } from './dialog/index';
import { computed, nextTick } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close', 'submit']);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

// Ctrl+Enter (Cmd+Enter on macOS) submits the modal from any focused element inside it.
// Handled in the capture phase so child elements (buttons, dropdown triggers, inputs with
// their own Enter handlers) never see the keystroke and cannot open or double-submit.
async function onKeydownCapture(event: KeyboardEvent) {
    if (event.key !== 'Enter' || !(event.ctrlKey || event.metaKey) || event.isComposing) {
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    // Inputs like the time and duration fields commit their value on blur, so blur first
    // and let the resulting model updates settle before submitting.
    const active = document.activeElement;
    if (active instanceof HTMLElement) {
        active.blur();
    }
    await nextTick();
    emit('submit');
}

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <Dialog :open="show" @update:open="close">
        <DialogContent :class="maxWidthClass">
            <div class="min-w-0" @keydown.capture="onKeydownCapture">
                <slot />
            </div>

            <DialogFooter>
                <slot name="footer" />
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
