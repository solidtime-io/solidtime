<script setup lang="ts">
import { cn } from '@/lib/utils';
import { DropdownMenuLabel, type DropdownMenuLabelProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<
    DropdownMenuLabelProps & { class?: HTMLAttributes['class']; inset?: boolean }
>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DropdownMenuLabel
        v-bind="forwardedProps"
        :class="cn('block px-2 py-2 text-xs text-gray-400', inset && 'pl-8', props.class)">
        <slot />
    </DropdownMenuLabel>
</template>
