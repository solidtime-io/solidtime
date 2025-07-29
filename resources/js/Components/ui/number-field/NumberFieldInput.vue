<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { NumberFieldInput } from 'reka-ui';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <NumberFieldInput
        data-slot="input"
        :class="
            cn(
                'flex h-[42px] w-full rounded-md border border-input-border bg-input-background px-3 py-2.5 text-sm text-center shadow-sm transition-colors placeholder:text-muted-foreground focus:border-input-border focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                props.class
            )
        " />
</template>
