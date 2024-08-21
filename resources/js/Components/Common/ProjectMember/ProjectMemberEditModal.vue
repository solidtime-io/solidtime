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
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import { UserIcon } from '@heroicons/vue/24/solid';
import ProjectMemberBillableRateModal from '@/Components/Common/ProjectMember/ProjectMemberBillableRateModal.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
const { updateProjectMember } = useProjectMembersStore();

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    projectMember: ProjectMember;
    name?: string;
}>();

const projectMemberBody = ref<UpdateProjectMemberBody>({
    billable_rate: props.projectMember.billable_rate,
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
                <span>Edit Project Member</span>
            </div>
        </template>

        <template #content>
            <ProjectMemberBillableRateModal
                :member-name="props.name"
                v-model:show="showBillableRateModal"
                @close="showBillableRateModal = false"
                @submit="submitBillableRate"></ProjectMemberBillableRateModal>
            <div class="grid grid-cols-3 items-center space-x-4">
                <div
                    class="col-span-3 sm:col-span-2 space-x-2 flex items-center">
                    <UserIcon class="w-4 text-muted"></UserIcon>
                    <span>{{ props.name }}</span>
                </div>
                <div class="col-span-3 sm:col-span-1 flex-1">
                    <InputLabel
                        for="billable_rate"
                        value="Billable Rate"></InputLabel>
                    <BillableRateInput
                        @keydown.enter="submit"
                        :currency="getOrganizationCurrencyString()"
                        name="billable_rate"
                        v-model="
                            projectMemberBody.billable_rate
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
                Update Project Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
