<script setup lang="ts">
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    name: string;
    selected: boolean;
}>();
</script>

<template>
    <div
        :class="
            twMerge(
                'flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out',

                props.selected ? 'bg-accent-300/20' : 'hover:bg-card-background'
            )
        ">
        <span>{{ name }}</span>
    </div>
</template>

<style scoped></style>
