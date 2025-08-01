<script setup lang="ts">
import { buttonVariants } from '@/Components/ui/button';
import { AlertDialogAction, type AlertDialogActionProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';
import { twMerge } from 'tailwind-merge';
const props = defineProps<AlertDialogActionProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <AlertDialogAction v-bind="delegatedProps" :class="twMerge(buttonVariants(), props.class)">
        <slot />
    </AlertDialogAction>
</template>
