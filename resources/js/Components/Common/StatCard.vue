<script setup lang="ts">
defineProps<{
    title: string;
    value?: string;
}>();
</script>

<template>
    <div class="rounded-lg bg-card-background border-card-border shadow-card border px-3.5 py-2.5">
        <dt class="font-medium text-sm text-text-secondary">{{ title }}</dt>
        <dd class="text-xl text-text-primary pt-1 font-medium">
            {{ value ?? '--' }}
        </dd>
    </div>
</template>

<style scoped></style>
