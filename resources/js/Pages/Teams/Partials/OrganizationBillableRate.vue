<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import type { UpdateOrganizationBody } from '@/packages/api/src';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import OrganizationBillableRateModal from '@/Components/Common/Organization/OrganizationBillableRateModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import { Checkbox } from '@/packages/ui/src';

const store = useOrganizationStore();
const { fetchOrganization, updateOrganization } = store;
const { organization } = storeToRefs(store);
const saving = ref(false);
const organizationBody = ref<UpdateOrganizationBody>({
    name: '',
    billable_rate: null as number | null,
    employees_can_see_billable_rates: false,
});

onMounted(async () => {
    await fetchOrganization();
    organizationBody.value = {
        name: organization.value?.name ?? '',
        billable_rate: organization.value?.billable_rate,
        employees_can_see_billable_rates:
            organization.value?.employees_can_see_billable_rates ?? false,
    };
});
const showConfirmationModal = ref(false);

async function submit() {
    saving.value = true;
    await updateOrganization(organizationBody.value);
    saving.value = false;
    showConfirmationModal.value = false;
}

function checkForConfirmationModal() {
    if (organizationBody.value.billable_rate === organization.value?.billable_rate) {
        submit();
    } else {
        showConfirmationModal.value = true;
    }
}
</script>

<template>
    <FormSection>
        <template #title> Billable Rate</template>

        <template #description>
            Configure the default billable rate for the organization.
        </template>

        <template #form>
            <OrganizationBillableRateModal
                v-model:show="showConfirmationModal"
                :new-billable-rate="organizationBody.billable_rate"
                @submit="submit"></OrganizationBillableRateModal>
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="organizationBillableRate">Organization Billable Rate</FieldLabel>
                <BillableRateInput
                    v-if="organization"
                    v-model="organizationBody.billable_rate"
                    :currency="getOrganizationCurrencyString()"
                    name="organizationBillableRate"></BillableRateInput>
            </Field>

            <div class="col-span-6 sm:col-span-4">
                <Field orientation="horizontal">
                    <Checkbox
                        v-if="organization"
                        id="organizationShowBillableRatesToEmployees"
                        v-model:checked="
                            organizationBody.employees_can_see_billable_rates
                        "></Checkbox>
                    <FieldLabel for="organizationShowBillableRatesToEmployees"
                        >Show Billable Rates to Employees</FieldLabel
                    >
                </Field>
            </div>
        </template>
        <template #actions>
            <PrimaryButton @click="checkForConfirmationModal">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
