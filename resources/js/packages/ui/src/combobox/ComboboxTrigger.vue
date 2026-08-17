<script setup lang="ts">
import type { ComboboxTriggerProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxTrigger, useForwardProps } from 'reka-ui';
import { cn } from '../utils/cn';

const props = defineProps<ComboboxTriggerProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <!-- Reka hardcodes tabindex="-1" here because it expects a ComboboxInput
         next to the trigger in the anchor to be the focusable control. Our input
         lives inside ComboboxList, so this trigger is the control and has to be
         tabbable. tabindex is a fallthrough attr, which Vue applies after Reka's
         own props and therefore wins.

         Reka also hardcodes aria-label="Show popup", which would otherwise be
         the accessible name. Pass :aria-label on the child element (it wins
         again, because Slot merges child props over attrs) to name the trigger
         after its current value. -->
    <ComboboxTrigger v-bind="forwarded" :class="cn(props.class)" tabindex="0">
        <slot />
    </ComboboxTrigger>
</template>
