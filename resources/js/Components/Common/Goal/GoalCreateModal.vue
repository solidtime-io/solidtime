<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref, watch } from 'vue';
import GoalFormFields from '@/Components/Common/Goal/GoalFormFields.vue';
import {
    type GoalFormErrors,
    emptyGoalFormData,
    goalFormDataToCreateBody,
    goalFormErrorsFromApi,
    validateGoalFormData,
} from '@/Components/Common/Goal/goalForm';
import { useGoalsStore } from '@/utils/useGoals';
import { useNotificationsStore } from '@/utils/notification';
import { getApiValidationFieldErrors, isApiValidationError } from '@/utils/apiValidation';

const { createGoal } = useGoalsStore();
const { addNotification } = useNotificationsStore();
const show = defineModel<boolean>('show', { default: false });
const saving = ref(false);
const goal = ref(emptyGoalFormData());
const errors = ref<GoalFormErrors>({});

watch(show, (isShown) => {
    if (isShown) {
        goal.value = emptyGoalFormData();
        errors.value = {};
    }
});

async function submit() {
    errors.value = validateGoalFormData(goal.value);
    if (Object.keys(errors.value).length > 0) {
        return;
    }
    saving.value = true;
    try {
        const created = await createGoal(goalFormDataToCreateBody(goal.value));
        if (created) {
            show.value = false;
        }
    } catch (error) {
        // Other errors were already shown as a notification by the store
        if (isApiValidationError(error)) {
            const apiErrors = goalFormErrorsFromApi(getApiValidationFieldErrors(error), goal.value);
            errors.value = apiErrors.errors;
            if (apiErrors.unmapped.length > 0) {
                addNotification('error', 'Failed to create goal', apiErrors.unmapped[0]);
            }
        }
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Goal </span>
            </div>
        </template>

        <template #content>
            <GoalFormFields v-model="goal" :errors="errors" @submit="submit"></GoalFormFields>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Create Goal
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
