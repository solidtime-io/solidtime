<script setup lang="ts">
import type { ComboboxInputEmits, ComboboxInputProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { Search } from '@lucide/vue';
import { ComboboxInput, useForwardPropsEmits } from 'reka-ui';
import { cn } from '../utils/cn';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<ComboboxInputProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<ComboboxInputEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <div class="relative items-center border-b border-card-background-separator">
        <ComboboxInput
            v-bind="{ ...$attrs, ...forwarded }"
            :class="
                cn(
                    'h-10 w-full border-0 rounded-none bg-transparent pl-9 pr-3 text-sm text-text-primary placeholder:text-text-tertiary focus:outline-none focus:ring-0',
                    props.class
                )
            " />
        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
            <Search class="size-4 text-text-tertiary" />
        </span>
    </div>
</template>
