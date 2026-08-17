<script setup lang="ts">
import type { ComboboxContentEmits, ComboboxContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxContent, ComboboxPortal, useForwardPropsEmits } from 'reka-ui';
import { cn } from '../utils/cn';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<ComboboxContentProps & { class?: HTMLAttributes['class'] }>(),
    {
        position: 'popper',
        align: 'start',
        sideOffset: 4,
        class: undefined,
    }
);
const emits = defineEmits<ComboboxContentEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <ComboboxPortal>
        <ComboboxContent
            v-bind="{ ...$attrs, ...forwarded }"
            :class="
                cn(
                    'z-50 w-[--reka-popper-anchor-width] min-w-60 overflow-hidden rounded-lg border border-popover-border bg-popover text-popover-foreground shadow-dropdown outline-none origin-[var(--reka-combobox-content-transform-origin)] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
                    props.class
                )
            ">
            <slot />
        </ComboboxContent>
    </ComboboxPortal>
</template>
