<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref, watch } from 'vue';
import type {
    ProjectMember,
    UpdateProjectMemberBody,
} from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import {
    type ProjectMemberRole,
    useProjectMembersStore,
} from '@/utils/useProjectMembers';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import ProjectMemberBillableRateModal from '@/Components/Common/ProjectMember/ProjectMemberBillableRateModal.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import ProjectMemberRoleSelect from '@/Components/Common/ProjectMember/ProjectMemberRoleSelect.vue';
const { updateProjectMember } = useProjectMembersStore();

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    projectMember: ProjectMember;
    name?: string;
}>();

const projectMemberBody = ref<UpdateProjectMemberBody>({
    billable_rate: props.projectMember.billable_rate,
    role: props.projectMember.role as ProjectMemberRole,
});
const showBillableRateModal = ref(false);
async function submit() {
    if (
        props.projectMember.billable_rate !==
        projectMemberBody.value.billable_rate
    ) {
        showBillableRateModal.value = true;
        return;
    }
    await updateProjectMember(props.projectMember.id, projectMemberBody.value);
    show.value = false;
    projectMemberBody.value = {
        billable_rate: null,
        role: 'normal',
    };
}

async function submitBillableRate() {
    await updateProjectMember(props.projectMember.id, projectMemberBody.value);
    show.value = false;
    showBillableRateModal.value = false;
}

watch(
    () => show.value,
    (value) => {
        if (value) {
            projectMemberBody.value = {
                billable_rate: props.projectMember.billable_rate,
                role: props.projectMember.role as ProjectMemberRole,
            };
        }
    }
);

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span>Edit Project Member "{{ props.name }}"</span>
            </div>
        </template>

        <template #content>
            <ProjectMemberBillableRateModal
                :member-name="props.name"
                v-model:show="showBillableRateModal"
                :new-billable-rate="projectMemberBody.billable_rate"
                @close="showBillableRateModal = false"
                @submit="submitBillableRate"></ProjectMemberBillableRateModal>
            <div>
                <div class="items-center space-y-4">
                    <div>
                        <InputLabel
                            value="Billable Rate"
                            for="billable_rate"></InputLabel>
                        <BillableRateInput
                            name="billable_rate"
                            :currency="getOrganizationCurrencyString()"
                            v-model="
                                projectMemberBody.billable_rate
                            "></BillableRateInput>
                    </div>
                    <div>
                        <InputLabel value="Role" class="mb-2"></InputLabel>
                        <ProjectMemberRoleSelect
                            v-model="
                                projectMemberBody.role
                            "></ProjectMemberRoleSelect>
                    </div>
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
                Update Project Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
