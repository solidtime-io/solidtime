<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-between py-1.5">
        <h3 class="text-text-primary font-medium text-sm flex items-center space-x-2">
            <component :is="icon" v-if="icon" class="w-4 lg:w-4 text-text-tertiary"></component>
            <span>
                {{ title }}
            </span>
        </h3>
        <div class="flex-1 flex justify-end items-center">
            <slot name="actions"></slot>
        </div>
    </div>
</template>

<style scoped></style>
