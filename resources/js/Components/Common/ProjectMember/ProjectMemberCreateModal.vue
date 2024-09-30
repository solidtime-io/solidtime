<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import type {
    CreateProjectMemberBody,
    ProjectMember,
} from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import MemberCombobox from '@/Components/Common/Member/MemberCombobox.vue';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import { InputLabel } from '@/packages/ui/src';
import ProjectMemberRoleSelect from '@/Components/Common/ProjectMember/ProjectMemberRoleSelect.vue';
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
    role: 'normal',
});

async function submit() {
    await createProjectMember(props.projectId, projectMember.value);
    show.value = false;
    projectMember.value = {
        member_id: '',
        billable_rate: null,
        role: 'normal',
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
            <div class="items-center space-y-4">
                <div>
                    <InputLabel value="Member" class="mb-2"></InputLabel>
                    <MemberCombobox
                        :hidden-members="props.existingMembers"
                        v-model="projectMember.member_id"></MemberCombobox>
                </div>
                <div>
                    <InputLabel
                        value="Billable Rate"
                        for="billable_rate"></InputLabel>
                    <BillableRateInput
                        name="billable_rate"
                        :currency="getOrganizationCurrencyString()"
                        v-model="
                            projectMember.billable_rate
                        "></BillableRateInput>
                </div>
                <div>
                    <InputLabel value="Role" class="mb-2"></InputLabel>
                    <ProjectMemberRoleSelect
                        v-model="projectMember.role"></ProjectMemberRoleSelect>
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
