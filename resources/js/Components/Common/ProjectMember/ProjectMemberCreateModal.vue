<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import type { CreateProjectMemberBody, ProjectMember } from '@/utils/api';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import MemberCombobox from '@/Components/Common/Member/MemberCombobox.vue';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
const { createProjectMember } = useProjectMembersStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    projectId: string;
    existingMembers: ProjectMember[];
}>();

const projectMember = ref<CreateProjectMemberBody>({
    member_id: '',
    billable_rate: null,
});

async function submit() {
    await createProjectMember(props.projectId, projectMember.value);
    show.value = false;
    projectMember.value = {
        member_id: '',
        billable_rate: null,
    };
}

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span>Add Project Member</span>
            </div>
        </template>

        <template #content>
            <div class="grid grid-cols-3 items-center space-x-4">
                <div class="col-span-3 sm:col-span-2">
                    <MemberCombobox
                        :hidden-members="props.existingMembers"
                        v-model="projectMember.member_id"></MemberCombobox>
                </div>
                <div class="col-span-3 sm:col-span-1 flex-1">
                    <BillableRateInput
                        name="billable_rate"
                        v-model="
                            projectMember.billable_rate
                        "></BillableRateInput>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false">Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Add Project Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
