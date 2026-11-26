<script setup lang="ts">
// Select of the Clockify preferences with its triangle caret, only used in the previews
defineProps<{
    value: string;
}>();
</script>

<template>
    <div
        class="flex h-7 min-w-0 items-center gap-1.5 rounded border border-input-border bg-input-background px-2 text-xs text-text-primary">
        <slot name="icon" />
        <span class="flex-1 truncate">{{ value }}</span>
        <svg viewBox="0 0 10 5" class="h-1.5 w-2.5 flex-none fill-text-secondary">
            <path d="M0 0h10L5 5z" />
        </svg>
    </div>
</template>
