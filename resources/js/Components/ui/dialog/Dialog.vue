<script setup lang="ts">
import {
    DialogRoot,
    type DialogRootEmits,
    type DialogRootProps,
    useForwardPropsEmits,
} from 'reka-ui';

const props = defineProps<DialogRootProps>();
const emits = defineEmits<DialogRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DialogRoot v-bind="forwarded">
        <slot />
    </DialogRoot>
</template>
