<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
    id: {
        type: String,
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        v-model="proxyChecked"
        type="checkbox"
        :id="id"
        :value="value"
        class="h-4 w-4 rounded bg-card-background border-input-border text-accent-500/80 focus:outline-none focus:ring-ring/50 focus-visible:outline-none focus-visible:ring-ring/50" />
</template>
