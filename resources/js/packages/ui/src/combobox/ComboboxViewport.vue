<script setup lang="ts">
import type { ComboboxViewportProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxViewport, useForwardProps } from 'reka-ui';
import { cn } from '../utils/cn';

const props = defineProps<ComboboxViewportProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <ComboboxViewport
        v-bind="forwarded"
        :class="cn('ui-combobox-viewport max-h-60 overflow-y-auto p-2', props.class)">
        <slot />
    </ComboboxViewport>
</template>

<style>
/* Reka's ComboboxViewport injects a global style that hides scrollbars; the
   attribute+class selector below is more specific and restores them. */
[data-reka-combobox-viewport].ui-combobox-viewport {
    scrollbar-width: thin;
    -ms-overflow-style: auto;
}
[data-reka-combobox-viewport].ui-combobox-viewport::-webkit-scrollbar {
    display: block;
    width: 8px;
}
[data-reka-combobox-viewport].ui-combobox-viewport::-webkit-scrollbar-thumb {
    background-color: rgb(127 127 127 / 0.4);
    border-radius: 4px;
}
</style>
