<script setup lang="ts">
defineProps<{
    title: string;
    value: string;
}>();
</script>

<template>
    <div
        class="rounded-lg bg-card-background border-card-border border px-4 py-3">
        <dt class="font-bold text-muted">{{ title }}</dt>
        <dd class="text-3xl text-white pt-1 font-bold">
            {{ value }}
        </dd>
    </div>
</template>

<style scoped></style>
