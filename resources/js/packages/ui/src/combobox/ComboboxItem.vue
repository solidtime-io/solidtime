<script setup lang="ts">
import type { ComboboxItemEmits, ComboboxItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxItem, useForwardPropsEmits } from 'reka-ui';
import { cn } from '../utils/cn';

const props = defineProps<ComboboxItemProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<ComboboxItemEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <ComboboxItem
        v-bind="forwarded"
        :class="
            cn(
                'flex w-full cursor-default items-center rounded-md px-2 py-1.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active',
                props.class
            )
        ">
        <slot />
    </ComboboxItem>
</template>
