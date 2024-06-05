<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import type { Member, UpdateMemberBody } from '@/utils/api';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useMembersStore } from '@/utils/useMembers';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';

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

async function submit() {
    await updateMember(props.member.id, memberBody.value);
    show.value = false;
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Member </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <BillableRateInput
                        focus
                        name="billable_rate"
                        v-model="memberBody.billable_rate"></BillableRateInput>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Update Client
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
