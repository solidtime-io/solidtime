<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import type { UpdateOrganizationBody } from '@/utils/api';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import OrganizationBillableRateModal from '@/Components/Common/Organization/OrganizationBillableRateModal.vue';

const store = useOrganizationStore();
const { fetchOrganization, updateOrganization } = store;
const { organization } = storeToRefs(store);
const saving = ref(false);
const organizationBody = ref<UpdateOrganizationBody>({
    name: '',
    billable_rate: null as number | null,
});

onMounted(async () => {
    await fetchOrganization();
    organizationBody.value = {
        name: organization.value?.name ?? '',
        billable_rate: organization.value?.billable_rate,
    };
});
const showConfirmationModal = ref(false);

async function submit() {
    saving.value = true;
    await updateOrganization(organizationBody.value);
    saving.value = false;
    showConfirmationModal.value = false;
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
                @submit="submit"
                :new-billable-rate="
                    organizationBody.billable_rate
                "></OrganizationBillableRateModal>
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
            <PrimaryButton @click="showConfirmationModal = true"
                >Save</PrimaryButton
            >
        </template>
    </FormSection>
</template>
