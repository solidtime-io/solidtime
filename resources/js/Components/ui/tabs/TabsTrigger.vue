<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsTrigger, type TabsTriggerProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes, type Component } from 'vue';

const props = defineProps<
    TabsTriggerProps & { class?: HTMLAttributes['class']; icon?: Component }
>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsTrigger
        v-bind="forwardedProps"
        :class="
            cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium  transition-all focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow',
                props.class
            )
        ">
        <div v-if="props.icon" class="mr-2 h-4 w-4">
            <props.icon />
        </div>
        <span class="truncate">
            <slot />
        </span>
    </TabsTrigger>
</template>
