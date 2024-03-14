<script setup lang="ts">
defineProps<{
    title: string;
    value: string;
}>();
</script>

<template>
    <div
        class="rounded-lg bg-card-background border-card-border border px-3.5 py-2.5">
        <dt class="font-bold text-sm text-muted">{{ title }}</dt>
        <dd class="text-2xl text-white pt-1 font-bold">
            {{ value }}
        </dd>
    </div>
</template>

<style scoped></style>
