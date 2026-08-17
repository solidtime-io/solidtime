<script setup lang="ts">
import type { ComboboxAnchorProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxAnchor, useForwardProps } from 'reka-ui';
import { cn } from '../utils/cn';

const props = defineProps<ComboboxAnchorProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <ComboboxAnchor v-bind="forwarded" :class="cn('w-full', props.class)">
        <slot />
    </ComboboxAnchor>
</template>
