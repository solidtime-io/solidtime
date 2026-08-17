<script setup lang="ts">
import type { ComboboxSeparatorProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxSeparator, useForwardProps } from 'reka-ui';
import { cn } from '../utils/cn';

const props = defineProps<ComboboxSeparatorProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <ComboboxSeparator
        v-bind="forwarded"
        :class="cn('my-1 h-px bg-card-background-separator', props.class)" />
</template>
