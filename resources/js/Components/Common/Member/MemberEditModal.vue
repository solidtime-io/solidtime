<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import type { Member, UpdateMemberBody } from '@/utils/api';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { type MemberBillableKey, useMembersStore } from '@/utils/useMembers';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MemberBillableRateModal from '@/Components/Common/Member/MemberBillableRateModal.vue';
import MemberBillableSelect from '@/Components/Common/Member/MemberBillableSelect.vue';
import { onMounted, watch } from 'vue';

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

async function submit(billableRateUpdateTimeEntries: boolean) {
    memberBody.value.billable_rate_update_time_entries =
        billableRateUpdateTimeEntries;
    await updateMember(props.member.id, memberBody.value);
    show.value = false;
    showBillableRateModal.value = false;
}

const showBillableRateModal = ref(false);
function openBillableRateModalIfNeeded() {
    if (memberBody.value.billable_rate !== props.member.billable_rate) {
        showBillableRateModal.value = true;
        show.value = false;
    } else {
        submit(false);
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
</script>

<template>
    <MemberBillableRateModal
        v-model:saving="saving"
        v-model:show="showBillableRateModal"
        :member-name="member.name"
        :newBillableRate="memberBody.billable_rate"
        @submit="submit"></MemberBillableRateModal>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Member </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1 flex space-x-5">
                    <div>
                        <InputLabel for="billableType" value="Billable" />
                        <MemberBillableSelect
                            class="mt-2"
                            name="billableType"
                            v-model="billableRateSelect"></MemberBillableSelect>
                    </div>
                    <div
                        class="flex-1"
                        v-if="billableRateSelect === 'custom-rate'">
                        <InputLabel
                            for="memberBillableRate"
                            value="Billable Rate" />
                        <BillableRateInput
                            focus
                            class="w-full"
                            @keydown.enter="openBillableRateModalIfNeeded()"
                            name="memberBillableRate"
                            v-model="
                                memberBody.billable_rate
                            "></BillableRateInput>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="openBillableRateModalIfNeeded()">
                Update Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
