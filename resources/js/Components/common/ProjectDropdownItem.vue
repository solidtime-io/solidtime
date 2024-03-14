<script setup lang="ts">
defineProps<{
    name: string;
    selected: boolean;
    color: string;
}>();
</script>

<template>
    <div
        class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
        <div
            :style="{ backgroundColor: color }"
            class="w-3 h-3 rounded-full"></div>
        <span>{{ name }}</span>
    </div>
</template>

<style scoped></style>
