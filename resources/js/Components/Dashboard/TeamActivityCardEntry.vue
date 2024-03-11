<script lang="ts" setup>
defineProps<{
    name: string;
    description: string;
    working?: boolean;
}>();
</script>

<template>
    <div class="px-4 py-3 grid grid-cols-3">
        <div class="col-span-2">
            <p class="font-semibold text-white pb-1">
                {{ name }}
            </p>
            <div class="text-muted font-medium">
                {{ description }}
            </div>
        </div>
        <div v-if="working" class="flex space-x-1.5 items-center justify-end">
            <span class="relative flex h-3 w-3 justify-center items-center">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                <span
                    class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            <span class="text-green-500 font-medium block pb-0.5">
                working
            </span>
        </div>
    </div>
</template>
