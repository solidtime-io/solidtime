<script setup lang="ts">
import { XMarkIcon, ChevronDownIcon } from '@heroicons/vue/16/solid';
import type { Component } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

defineProps<{
    icon: Component;
    label: string;
    filterName: string;
}>();

defineEmits<{
    remove: [];
}>();

defineSlots<{
    default(): void;
}>();
</script>

<template>
    <div
        class="inline-flex items-center gap-0.5 rounded-md bg-tertiary dark:bg-secondary border border-border-secondary">
        <DropdownMenu>
            <DropdownMenuTrigger
                class="inline-flex items-center gap-1.5 px-2 py-1 text-sm hover:bg-quaternary dark:hover:bg-tertiary rounded-l-md transition-colors whitespace-nowrap">
                <component :is="icon" class="h-3.5 w-3.5 text-icon-default" />
                <span class="font-medium text-foreground">{{ filterName }}</span>
                <span class="text-muted-foreground">is</span>
                <span class="text-foreground">{{ label }}</span>
                <ChevronDownIcon class="h-3 w-3 text-muted-foreground" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start">
                <slot />
            </DropdownMenuContent>
        </DropdownMenu>

        <button
            class="px-1.5 py-1 hover:bg-quaternary dark:hover:bg-tertiary h-full rounded-r-md transition-colors group border-l border-border-secondary"
            @click="$emit('remove')">
            <XMarkIcon class="h-3.5 w-3.5 text-muted-foreground group-hover:text-foreground" />
        </button>
    </div>
</template>
