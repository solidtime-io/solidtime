<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import type { UpdateOrganizationBody } from '@/utils/api';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';

const store = useOrganizationStore();
const { fetchOrganization, updateOrganization } = store;
const { organization } = storeToRefs(store);

const organizationBody = ref<UpdateOrganizationBody>({
    name: '',
    billable_rate: null,
});

onMounted(async () => {
    await fetchOrganization();
    organizationBody.value = {
        name: organization.value?.name ?? '',
        billable_rate: organization.value?.billable_rate,
    };
});

function submit() {
    updateOrganization(organizationBody.value);
}
</script>

<template>
    <FormSection>
        <template #title> Billable Rate</template>

        <template #description>
            Configure the default billable rate for the organization.
        </template>

        <template #form>
            <!-- Organization Owner Information -->
            <div class="col-span-6">
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel
                        for="organizationBillableRate"
                        value="Organization Billable Rate" />
                    <BillableRateInput
                        v-if="organization"
                        v-model="organizationBody.billable_rate"
                        name="organizationBillableRate"></BillableRateInput>
                </div>
            </div>
        </template>
        <template #actions>
            <PrimaryButton @click="submit">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
