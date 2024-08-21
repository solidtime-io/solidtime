<script setup lang="ts">
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';

defineProps<{
    label: string;
}>();
</script>

<template>
    <Dropdown align="bottom-end">
        <template #trigger>
            <button
                class="focus-visible:outline-none focus-visible:bg-card-background rounded-full focus-visible:ring-1 focus-visible:ring-input-border-active focus-visible:opacity-100 hover:bg-card-background group-hover:opacity-100 opacity-20 transition-opacity text-muted"
                :aria-label="label">
                <svg
                    class="h-10 w-10 p-2 rounded-full"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        fill="none"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 5.92A.96.96 0 1 0 12 4a.96.96 0 0 0 0 1.92m0 7.04a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92M12 20a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92" />
                </svg>
            </button>
        </template>
        <template #content>
            <slot></slot>
        </template>
    </Dropdown>
</template>

<style scoped></style>
