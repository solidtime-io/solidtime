<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>

<template>
    <h3
        class="text-white font-bold pb-4 text-base flex items-center space-x-2.5">
        <component
            v-if="icon"
            :is="icon"
            class="w-6 text-icon-default"></component>
        <span>
            {{ title }}
        </span>
    </h3>
</template>

<style scoped></style>
