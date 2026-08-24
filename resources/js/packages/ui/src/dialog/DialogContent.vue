<script setup lang="ts">
import { cn } from '../utils/cn';
import {
    DialogContent,
    type DialogContentEmits,
    type DialogContentProps,
    DialogOverlay,
    DialogPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<DialogContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <DialogPortal>
        <DialogOverlay
            class="fixed top-0 left-0 z-50 w-screen h-screen [height:100dvh] backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
            <div class="absolute inset-0 bg-default-background opacity-30" />
            <div class="absolute inset-0 overflow-y-auto overscroll-contain flex items-start justify-center px-2">
                <DialogContent
                    v-bind="forwarded"
                    :class="
                        cn(
                            'my-3 md:my-14 xl:my-24 bg-default-background grid w-full max-w-lg border border-border-tertiary shadow-lg duration-200 rounded-lg outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
                            props.class
                        )
                    ">
                    <slot />
                </DialogContent>
            </div>
        </DialogOverlay>
    </DialogPortal>
</template>
