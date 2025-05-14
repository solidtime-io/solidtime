<script setup lang="ts">
import Badge from '@/packages/ui/src/Badge.vue';

const props = defineProps<{
    icon: Component;
    title: string;
    count?: number;
    active?: boolean;
}>();
import { type Component, computed } from 'vue';
import { twMerge } from 'tailwind-merge';

const activeClass = computed(() => {
    if (props.active) {
        return 'border-accent-300/50 bg-accent-300/10 hover:bg-accent-300/20';
    }
    return '';
});
</script>

<template>
    <Badge
        size="large"
        tag="button"
        :class="
            twMerge(
                'cursor-pointer bg-input-background hover:bg-card-background transition flex',
                activeClass
            )
        ">
        <component
            :is="icon"
            class="-ml-0.5 h-4 w-4 text-text-quaternary"></component>
        <span class="text-nowrap"> {{ title }} </span>
        <div
            v-if="count"
            class="bg-accent-300/20 w-5 h-5 font-medium rounded flex items-center transition justify-center">
            {{ count }}
        </div>
    </Badge>
</template>

<style scoped></style>
