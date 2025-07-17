<script setup lang="ts">
import { Button } from '@/Components/ui/button';

const props = defineProps<{
    icon: Component;
    title: string;
    count?: number;
    active?: boolean;
}>();
import { type Component, computed } from 'vue';
import { twMerge } from 'tailwind-merge';

const activeClass = computed(() => {
    if (props.active) {
        return 'border-accent-300/50 bg-accent-50 hover:bg-accent-100 dark:border-accent-300/50 dark:bg-accent-300/5 dark:hover:bg-accent-300/10';
    }
    return '';
});

const iconClass = computed(() => {
    return twMerge(
        '-ml-0.5 h-4 w-4',
        props.active ? 'dark:text-accent-300/80 text-accent-400/80' : 'text-text-quaternary'
    );
});
</script>

<template>
    <Button
        variant="outline"
        size="sm"
        :class="
            twMerge(
                activeClass
            )
        ">
        <component
            :is="icon"
            :class="iconClass"
        ></component>
        <span class="text-nowrap"> {{ title }} </span>
        <div
            v-if="count"
            class="bg-accent-300/20 w-5 h-5 font-medium rounded flex items-center transition justify-center">
            {{ count }}
        </div>
    </Button>
</template>

<style scoped></style>
