<script setup lang="ts">
defineProps<{
    name: string;
    selected: boolean;
    color: string;
}>();
</script>

<template>
    <div
        class="flex justify-between items-center w-full text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
        <div class="flex space-x-3 items-center px-3 py-1.5">
            <div
                :style="{ backgroundColor: color }"
                class="w-3 h-3 rounded-full"></div>
            <span>{{ name }}</span>
        </div>
        <slot name="actions"></slot>
    </div>
</template>

<style scoped></style>
