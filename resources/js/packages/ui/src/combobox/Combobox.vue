<script setup lang="ts">
import type { ComboboxRootEmits, ComboboxRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxRoot, useForwardPropsEmits } from 'reka-ui';
import { cn } from '../utils/cn';

const props = defineProps<ComboboxRootProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<ComboboxRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <!-- Keep the trigger inside this root. Reka treats anything within the root
         element as "not outside", so it suppresses its own dismissal for trigger
         clicks and ComboboxInput's blur-close ignores them. Putting the trigger
         in a separate Popover instead gives two competing open states, and the
         popover then closes on mousedown and reopens on the following click. -->
    <ComboboxRoot v-slot="slotProps" v-bind="forwarded" :class="cn('min-w-0', props.class)">
        <slot v-bind="slotProps" />
    </ComboboxRoot>
</template>
