<template>
    <section class="flex overflow-hidden flex-col gap-1.5">
        <CardTitle :title="title" :icon="icon"></CardTitle>

        <div
            class="flex-1 flex items-stretch rounded-lg bg-card-background border border-card-border">
            <div class="w-full flex flex-col">
                <slot></slot>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import CardTitle from '@/packages/ui/src/CardTitle.vue';

defineProps<{
    title: string;
    icon?: Component;
}>();
</script>
