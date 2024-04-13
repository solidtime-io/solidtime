<template>
    <section class="flex flex-col">
        <CardTitle :title="title" :icon="icon"></CardTitle>

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
import CardTitle from '@/Components/Common/CardTitle.vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>
