<script setup lang="ts">
import { computed } from 'vue';
import type { VacationRequestStatus } from '@/utils/useVacationRequests';

const props = defineProps<{ status: VacationRequestStatus }>();

const config = computed(() => {
    switch (props.status) {
        case 'approved':
            return {
                label: 'Approved',
                classes: 'border border-input-border text-text-primary bg-transparent',
            };
        case 'pending':
            return {
                label: 'Pending',
                classes: 'border border-blue-400 text-blue-600 bg-transparent',
            };
        case 'rejected':
            return {
                label: 'Rejected',
                classes: 'border border-red-400 text-red-600 bg-transparent',
            };
        case 'withdrawn':
            return {
                label: 'Request withdrawn',
                classes: 'text-text-quaternary bg-transparent border border-transparent',
            };
    }
});
</script>

<template>
    <span
        class="inline-flex items-center px-2.5 py-1 rounded text-xs font-medium"
        :class="config.classes">
        {{ config.label }}
    </span>
</template>
