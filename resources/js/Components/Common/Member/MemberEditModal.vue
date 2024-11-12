<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref } from 'vue';
import type { Member, UpdateMemberBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { type MemberBillableKey, useMembersStore } from '@/utils/useMembers';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import MemberBillableRateModal from '@/Components/Common/Member/MemberBillableRateModal.vue';
import MemberBillableSelect from '@/Components/Common/Member/MemberBillableSelect.vue';
import { onMounted, watch } from 'vue';
import MemberRoleSelect from '@/Components/Common/Member/MemberRoleSelect.vue';
import MemberOwnershipTransferConfirmModal from '@/Components/Common/Member/MemberOwnershipTransferConfirmModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';

const { updateMember } = useMembersStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    member: Member;
}>();

const memberBody = ref<UpdateMemberBody>({
    // @ts-expect-error - The role value is always valid
    role: props.member.role,
    billable_rate: props.member.billable_rate,
});

async function submitBillableRate() {
    if (memberBody.value.role === 'owner' && props.member.role !== 'owner') {
        show.value = false;
        showOwnershipTransferConfirmModal.value = true;
    } else {
        await submit();
    }
}

async function submit() {
    await updateMember(props.member.id, memberBody.value);
    show.value = false;
    showBillableRateModal.value = false;
    showOwnershipTransferConfirmModal.value = false;
}

const showBillableRateModal = ref(false);
const showOwnershipTransferConfirmModal = ref(false);

function saveWithChecks() {
    if (memberBody.value.billable_rate !== props.member.billable_rate) {
        showBillableRateModal.value = true;
        show.value = false;
    } else if (
        memberBody.value.role === 'owner' &&
        props.member.role !== 'owner'
    ) {
        show.value = false;
        showOwnershipTransferConfirmModal.value = true;
    } else {
        submitBillableRate();
    }
}

const billableRateSelect = ref<MemberBillableKey>('default-rate');

onMounted(() => {
    if (props.member.billable_rate !== null) {
        billableRateSelect.value = 'custom-rate';
    } else {
        billableRateSelect.value = 'default-rate';
    }
});
watch(billableRateSelect, () => {
    if (billableRateSelect.value === 'default-rate') {
        memberBody.value.billable_rate = null;
    } else if (billableRateSelect.value === 'custom-rate') {
        memberBody.value.billable_rate = props.member.billable_rate ?? 0;
    }
});

const roleDescriptionTexts = {
    'owner':
        'The owner has full access of the organization. The owner is the only role that can: delete the organization, transfer the ownership to another user and access to the billing settings',
    'admin':
        'The admin has full access to the organization, except for the stuff that only the owner can do.',
    'manager':
        'The manager has full access to projects, clients, tags, time entries, and reports, but can not manage the organization or the users.',
    'employee':
        'An employee is a user that is only using the application to track time, but has no administrative rights.',
    'placeholder':
        'Placeholder users can not do anything in the organization. They are not billed and can be used to remove users from the organization without deleting their time entries.',
};

const roleDescription = computed(() => {
    if (
        memberBody.value.role &&
        memberBody.value.role in roleDescriptionTexts
    ) {
        return roleDescriptionTexts[memberBody.value.role];
    }
    return '';
});
</script>

<template>
    <MemberBillableRateModal
        v-model:saving="saving"
        v-model:show="showBillableRateModal"
        :member-name="member.name"
        :newBillableRate="memberBody.billable_rate"
        @submit="submitBillableRate"></MemberBillableRateModal>
    <MemberOwnershipTransferConfirmModal
        :member-name="member.name"
        v-model:show="showOwnershipTransferConfirmModal"
        @submit="submit"></MemberOwnershipTransferConfirmModal>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Member </span>
            </div>
        </template>

        <template #content>
            <div class="pb-5 pt-2 divide-y divide-border-secondary">
                <div class="pb-5 flex space-x-6">
                    <div>
                        <InputLabel for="role" value="Role" />
                        <MemberRoleSelect
                            class="mt-2"
                            name="role"
                            v-model="memberBody.role"></MemberRoleSelect>
                    </div>
                    <div class="flex-1 text-xs flex items-center pt-6">
                        <p>{{ roleDescription }}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4 pt-5">
                    <div class="col-span-6 sm:col-span-4 flex-1 flex space-x-5">
                        <div>
                            <InputLabel for="billableType" value="Billable" />
                            <MemberBillableSelect
                                class="mt-2"
                                name="billableType"
                                v-model="
                                    billableRateSelect
                                "></MemberBillableSelect>
                        </div>
                        <div
                            class="flex-1"
                            v-if="billableRateSelect === 'custom-rate'">
                            <InputLabel
                                for="memberBillableRate"
                                class="mb-2"
                                value="Billable Rate" />
                            <BillableRateInput
                                focus
                                class="w-full"
                                :currency="getOrganizationCurrencyString()"
                                @keydown.enter="saveWithChecks()"
                                name="memberBillableRate"
                                v-model="
                                    memberBody.billable_rate
                                "></BillableRateInput>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="saveWithChecks()">
                Update Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
