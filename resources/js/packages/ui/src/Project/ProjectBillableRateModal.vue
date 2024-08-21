<script setup lang="ts">
import { formatCents } from '@/packages/ui/src/utils/money';
import BillableRateModal from '@/packages/ui/src/BillableRateModal.vue';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

defineProps<{
    newBillableRate?: number | null;
    projectName: string;
    currency: string;
}>();

defineEmits<{
    submit: [];
}>();
</script>

<template>
    <BillableRateModal
        @submit="$emit('submit')"
        v-model:show="show"
        v-model:saving="saving"
        title="Update Project Billable Rate">
        <p class="py-1 text-center">
            The billable rate of {{ projectName }} will be updated to
            <strong>{{
                newBillableRate
                    ? formatCents(newBillableRate, currency)
                    : ' the default rate of the organization member'
            }}</strong
            >.
        </p>
        <p class="py-1 text-center font-semibold max-w-md mx-auto">
            Do you want to update all existing time entries, where the project
            billable rate applies as well?
        </p>
    </BillableRateModal>
</template>

<style scoped></style>
