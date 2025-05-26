<script setup lang="ts">
import { getOrganizationCurrencyString } from '@/utils/money';
import BillableRateModal from '@/packages/ui/src/BillableRateModal.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import { inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

const organization = inject<ComputedRef<Organization>>('organization');

defineProps<{
    newBillableRate?: number | null;
}>();

defineEmits<{
    submit: [];
}>();
</script>

<template>
    <BillableRateModal
        v-model:show="show"
        v-model:saving="saving"
        title="Update Organization Billable Rate"
        @submit="$emit('submit')">
        <p class="py-0.5 text-center">
            The organization billable rate will be updated to
            <strong>{{
                newBillableRate
                    ? formatCents(
                          newBillableRate,
                          getOrganizationCurrencyString(),
                          organization?.currency_format,
                          organization?.currency_symbol,
                          organization?.number_format
                      )
                    : ' none.'
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
