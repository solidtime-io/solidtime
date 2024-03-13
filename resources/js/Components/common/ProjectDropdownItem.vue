<script setup lang="ts">
defineProps<{
    name: string;
    selected: boolean;
    color: string;
}>();
</script>

<template>
    <div
        class="flex items-center space-x-3 w-full px-4 py-2.5 text-start text-base font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
        <div
            :style="{ backgroundColor: color }"
            class="w-4 h-4 rounded-full"></div>
        <span>{{ name }}</span>
    </div>
</template>

<style scoped></style>
