<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import { SelectIcon, SelectTrigger, type SelectTriggerProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<
    SelectTriggerProps & { size?: 'small'; class?: HTMLAttributes['class'] }
>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
const sizeClasses = computed(() => {
    return props.size === 'small' ? 'h-[34px] text-sm' : 'h-[42px]';
});
</script>

<template>
    <SelectTrigger
        v-bind="forwardedProps"
        :class="
            cn(
                'flex w-full items-center justify-between whitespace-nowrap rounded-md border border-input-border bg-input-background px-3 py-2.5 shadow-sm data-[placeholder]:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:truncate text-start',
                sizeClasses,
                props.class
            )
        ">
        <slot />
        <SelectIcon as-child>
            <ChevronDown class="w-4 h-4 opacity-50 shrink-0" />
        </SelectIcon>
    </SelectTrigger>
</template>
