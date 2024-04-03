<template>
    <section class="flex flex-col">
        <h3
            class="text-white font-bold pb-4 textbase flex items-center space-x-2.5">
            <component
                v-if="icon"
                :is="icon"
                class="w-6 text-icon-default"></component>
            <span>{{ title }}</span>
        </h3>
        <div
            class="rounded-lg bg-card-background border border-card-border flex-1 flex items-center">
            <div class="divide-y divide-card-background-separator w-full">
                <slot></slot>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>
