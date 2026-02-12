<script setup lang="ts">
import { TabsTrigger } from '@/Components/ui/tabs';
import { twMerge } from 'tailwind-merge';
import type { Component } from 'vue';

const props = defineProps<{
    value: string;
    class?: string;
    icon?: Component;
}>();
</script>

<template>
    <TabsTrigger
        :value="value"
        :icon="icon"
        :class="
            twMerge(
                'rounded-md px-2 sm:px-3 border py-1.5 text-xs sm:text-sm font-medium text-text-tertiary hover:text-text-primary focus-visible:outline-none data-[state=active]:bg-tab-background data-[state=active]:border-input-border data-[state=active]:text-text-primary border-tab-border',
                props.class
            )
        ">
        <slot></slot>
    </TabsTrigger>
</template>

<style scoped></style>
