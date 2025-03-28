<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    icon: Component;
    title: string;
}>();
</script>

<template>
    <h3
        class="text-text-primary font-semibold text-sm sm:text-base flex items-center space-x-2 sm:space-x-2.5">
        <component :is="icon" class="w-5 sm:w-6 text-icon-default"></component>
        <span> {{ title }} </span>
    </h3>
</template>

<style scoped></style>
