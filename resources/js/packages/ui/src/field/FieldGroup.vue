<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '../utils/cn';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        data-slot="field-group"
        :class="
            cn(
                'group/field-group @container/field-group flex w-full flex-col gap-6 data-[slot=checkbox-group]:gap-3 [&>[data-slot=field-group]]:gap-4',
                props.class
            )
        ">
        <slot />
    </div>
</template>
