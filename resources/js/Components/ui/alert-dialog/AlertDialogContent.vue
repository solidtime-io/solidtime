<script setup lang="ts">
import {
    AlertDialogContent,
    type AlertDialogContentEmits,
    type AlertDialogContentProps,
    AlertDialogOverlay,
    AlertDialogPortal,
    useForwardPropsEmits,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<AlertDialogContentProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<AlertDialogContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <AlertDialogPortal>
        <AlertDialogOverlay
            class="fixed inset-0 z-50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0">
            <div class="absolute inset-0 bg-default-background opacity-30" />
        </AlertDialogOverlay>
        <div
            class="fixed top-0 left-0 z-50 pointer-events-none w-screen h-screen flex items-start px-2 pt-3 md:pt-14 xl:pt-24 justify-center overflow-auto">
            <AlertDialogContent
                v-bind="forwarded"
                :class="
                    cn(
                        'pointer-events-auto bg-default-background grid w-full max-w-lg gap-4 border border-border-tertiary p-6 shadow-lg duration-200 rounded-lg outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
                        props.class
                    )
                ">
                <slot />
            </AlertDialogContent>
        </div>
    </AlertDialogPortal>
</template>
