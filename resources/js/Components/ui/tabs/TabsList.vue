<script setup lang="ts">
import { cn } from '@/lib/utils';
import { TabsList, type TabsListProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <TabsList
        v-bind="delegatedProps"
        :class="
            cn('inline-flex items-center rounded-lg bg-muted text-muted-foreground', props.class)
        ">
        <slot />
    </TabsList>
</template>
