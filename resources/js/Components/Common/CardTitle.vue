<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>

<template>
    <div class="flex w-full items-center justify-between pb-2.5 sm:pb-4">
        <h3
            class="text-white font-bold text-base flex items-center space-x-2.5">
            <component
                v-if="icon"
                :is="icon"
                class="w-6 text-icon-default"></component>
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
