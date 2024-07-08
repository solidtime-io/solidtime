<script setup lang="ts">
import { formatCents } from '../../../utils/money';
import BillableRateModal from '@/Components/Common/BillableRateModal.vue';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

defineProps<{
    newBillableRate?: number | null;
    memberName: string;
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
        title="Update Member Billable Rate">
        <p class="py-1 text-center">
            The billable rate of {{ memberName }} will be updated to
            <strong>{{
                newBillableRate
                    ? formatCents(newBillableRate)
                    : ' the default rate of the organization'
            }}</strong
            >.
        </p>
        <p class="py-1 text-center font-semibold max-w-md mx-auto">
            Do you want to update all existing time entries, where the member
            billable rate applies as well?
        </p>
    </BillableRateModal>
</template>

<style scoped></style>
