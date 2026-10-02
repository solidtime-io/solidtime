<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref, watch } from 'vue';
import type { Goal } from '@/packages/api/src';
import GoalFormFields from '@/Components/Common/Goal/GoalFormFields.vue';
import {
    type GoalFormErrors,
    type FilterIdKey,
    type GoalFormData,
    getUnavailableGoalFilters,
    goalFormDataToUpdateBody,
    goalFormErrorsFromApi,
    goalToFormData,
    type KnownFilterIds,
    type UnavailableGoalFilter,
    validateGoalFormData,
    withoutUnavailableGoalFilters,
} from '@/Components/Common/Goal/goalForm';
import { useGoalsStore } from '@/utils/useGoals';
import { useNotificationsStore } from '@/utils/notification';
import { getApiValidationFieldErrors, isApiValidationError } from '@/utils/apiValidation';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { canViewMembers } from '@/utils/permissions';

const { updateGoal } = useGoalsStore();
const { addNotification } = useNotificationsStore();
const show = defineModel<boolean>('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    goal: Goal;
}>();

const projectsQuery = useProjectsQuery();
const tasksQuery = useTasksQuery();
const clientsQuery = useClientsQuery();
const tagsQuery = useTagsQuery();
const membersQuery = canViewMembers() ? useMembersQuery() : null;

function idSet(entities: { id: string }[] | undefined): Set<string> | null {
    return entities === undefined ? null : new Set(entities.map((entity) => entity.id));
}

const filterQueries = {
    project_ids: projectsQuery,
    task_ids: tasksQuery,
    client_ids: clientsQuery,
    tag_ids: tagsQuery,
    member_ids: membersQuery,
};

const knownFilterIds = computed<KnownFilterIds>(() => ({
    project_ids: projectsQuery.isSuccess.value ? idSet(projectsQuery.data.value?.data) : null,
    task_ids: tasksQuery.isSuccess.value ? idSet(tasksQuery.data.value?.data) : null,
    client_ids: clientsQuery.isSuccess.value ? idSet(clientsQuery.data.value?.data) : null,
    tag_ids: tagsQuery.isSuccess.value ? idSet(tagsQuery.data.value?.data) : null,
    // Placeholders can not be selected in the member filter either
    member_ids: membersQuery?.isSuccess.value
        ? idSet(membersQuery.data.value?.data.filter((member) => member.is_placeholder === false))
        : null,
}));

const form = ref(goalToFormData(props.goal));
const errors = ref<GoalFormErrors>({});

const pendingForm = ref<GoalFormData | null>(null);
const unavailableFilters = ref<UnavailableGoalFilter[]>([]);
const checkingFilters = computed(() =>
    (Object.keys(filterQueries) as FilterIdKey[]).some(
        (key) => form.value[key].some((id) => id !== 'none') && filterQueries[key]?.isFetching.value
    )
);

watch(show, (isShown) => {
    pendingForm.value = null;
    if (isShown) {
        form.value = goalToFormData(props.goal);
        errors.value = {};
    }
});

async function submit() {
    if (saving.value || checkingFilters.value) return;
    errors.value = validateGoalFormData(form.value);
    if (Object.keys(errors.value).length > 0) {
        return;
    }
    unavailableFilters.value = getUnavailableGoalFilters(form.value, knownFilterIds.value);
    if (unavailableFilters.value.length > 0) {
        pendingForm.value = { ...form.value };
        return;
    }
    await save(form.value);
}

async function save(data: GoalFormData, keepExistingFilters = false) {
    if (saving.value) return;
    const body = goalFormDataToUpdateBody(data);
    const originalFilters = goalFormDataToUpdateBody(goalToFormData(props.goal)).filters;
    // Omitting unchanged filters also lets unrelated edits preserve deleted references.
    if (keepExistingFilters || JSON.stringify(body.filters) === JSON.stringify(originalFilters)) {
        delete body.filters;
    }
    saving.value = true;
    try {
        const updated = await updateGoal(props.goal.id, body);
        if (updated) {
            show.value = false;
        }
    } catch (error) {
        // Other errors were already shown as a notification by the store
        if (isApiValidationError(error)) {
            const apiErrors = goalFormErrorsFromApi(getApiValidationFieldErrors(error), form.value);
            errors.value = apiErrors.errors;
            // Back from the unavailable filters warning to the form that shows the errors
            pendingForm.value = null;
            if (apiErrors.unmapped.length > 0) {
                addNotification('error', 'Failed to update goal', apiErrors.unmapped[0]);
            }
        }
    } finally {
        saving.value = false;
    }
}

async function keepExistingFilters() {
    if (pendingForm.value) await save(pendingForm.value, true);
}

async function removeUnavailableFilters() {
    if (pendingForm.value) {
        await save(withoutUnavailableGoalFilters(pendingForm.value, unavailableFilters.value));
    }
}
</script>

<template>
    <DialogModal :closeable="!saving" :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span v-if="pendingForm">Unavailable filter items</span>
                <span v-else> Edit Goal {{ props.goal.name }} </span>
            </div>
        </template>

        <template #content>
            <div v-if="pendingForm" class="space-y-4" data-testid="goal_unavailable_filters">
                <p>
                    Some selected filter items are unavailable. They may have been deleted or you
                    may no longer have access. Removing them changes which time entries count toward
                    this goal.
                </p>
                <ul class="list-disc pl-5 space-y-2">
                    <li v-for="filter in unavailableFilters" :key="filter.key">
                        Unavailable {{ filter.label }}: {{ filter.ids.length }}.
                        <strong v-if="filter.removesRestriction">
                            This will count all {{ filter.label }}, subject to the remaining
                            filters.
                        </strong>
                    </li>
                </ul>
                <p>
                    Keeping existing filters saves your other edits and discards any filter edits
                    made in this form.
                </p>
            </div>
            <GoalFormFields
                v-else
                v-model="form"
                :errors="errors"
                disable-target
                @submit="submit"></GoalFormFields>
        </template>
        <template #footer>
            <template v-if="pendingForm">
                <SecondaryButton :disabled="saving" @click="pendingForm = null">
                    Back
                </SecondaryButton>
                <SecondaryButton class="ms-3" :disabled="saving" @click="removeUnavailableFilters">
                    Remove unavailable items
                </SecondaryButton>
                <PrimaryButton class="ms-3" :disabled="saving" @click="keepExistingFilters">
                    Keep existing filters
                </PrimaryButton>
            </template>
            <template v-else>
                <SecondaryButton :disabled="saving" @click="show = false"> Cancel </SecondaryButton>
                <PrimaryButton
                    class="ms-3"
                    :class="{ 'opacity-25': saving }"
                    :disabled="saving || checkingFilters"
                    @click="submit">
                    Update Goal
                </PrimaryButton>
            </template>
        </template>
    </DialogModal>
</template>

<style scoped></style>
