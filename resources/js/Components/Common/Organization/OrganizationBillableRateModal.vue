<script setup lang="ts">
import { formatCents } from '../../../utils/money';
import BillableRateModal from '@/Components/Common/BillableRateModal.vue';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

defineProps<{
    newBillableRate?: number | null;
}>();

defineEmits<{
    submit: [billable_rate_update_time_entries: boolean];
}>();
</script>

<template>
    <BillableRateModal
        @submit="(...args) => $emit('submit', ...args)"
        v-model:show="show"
        v-model:saving="saving"
        title="Update Organization Billable Rate">
        <p class="py-0.5 text-center">
            The organization billable rate will be updated to
            <strong>{{
                newBillableRate ? formatCents(newBillableRate) : ' none.'
            }}</strong
            >.
        </p>
        <p class="py-0.5 text-center font-semibold">
            Do you want to update all existing time entries, where the
            organization billable rate applies as well?
        </p>
    </BillableRateModal>
</template>

<style scoped></style>
