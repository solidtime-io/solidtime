<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AlertDialogDescription, type AlertDialogDescriptionProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<AlertDialogDescriptionProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <AlertDialogDescription
        v-bind="delegatedProps"
        :class="cn('text-sm text-muted-foreground', props.class)">
        <slot />
    </AlertDialogDescription>
</template>
