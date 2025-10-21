<script lang="ts" setup>
defineProps<{
    name: string;
    description: string | null;
    working?: boolean;
}>();
</script>

<template>
    <div class="px-4 py-2 2xl:py-3 border-b border-b-background-separator">
        <div class="col-span-2">
            <div class="flex justify-between">
                <p
                    class="font-semibold text-sm min-w-0 overflow-ellipsis overflow-hidden flex-1 text-text-primary">
                    {{ name }}
                </p>
                <div v-if="working" class="flex space-x-1.5 items-center justify-end">
                    <span class="relative flex h-3 w-3 justify-center items-center">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="text-green-500 font-medium text-sm block pb-0.5"> working </span>
                </div>
            </div>
            <div
                class="text-text-secondary text-sm font-medium text-ellipsis whitespace-nowrap max-w-full overflow-hidden">
                {{ description }}
            </div>
        </div>
    </div>
</template>
