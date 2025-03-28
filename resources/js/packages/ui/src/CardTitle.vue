<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-between pb-2.5 lg:pb-4">
        <h3
            class="text-text-primary font-semibold text-sm lg:text-base flex items-center space-x-2 lg:space-x-2.5">
            <component
                :is="icon"
                v-if="icon"
                class="w-5 lg:w-6 text-icon-default"></component>
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
