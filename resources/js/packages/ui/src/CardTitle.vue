<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-between pb-2.5 lg:pb-3">
        <h3
            class="text-text-primary font-medium text-sm lg:text-base flex items-center space-x-1.5 lg:space-x-2">
            <component
                :is="icon"
                v-if="icon"
                class="w-4 lg:w-4 text-icon-default"></component>
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
