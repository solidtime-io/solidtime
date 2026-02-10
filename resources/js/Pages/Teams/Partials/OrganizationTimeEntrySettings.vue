<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import { Checkbox } from '@/packages/ui/src';
import type { UpdateOrganizationBody } from '@/packages/api/src';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import { useMutation, useQueryClient } from '@tanstack/vue-query';

const store = useOrganizationStore();
const { updateOrganization } = store;
const { organization } = storeToRefs(store);
const queryClient = useQueryClient();

const form = ref<{
    prevent_overlapping_time_entries: boolean;
    employees_can_manage_tasks: boolean;
}>({
    prevent_overlapping_time_entries: false,
    employees_can_manage_tasks: false,
});

onMounted(async () => {
    form.value.prevent_overlapping_time_entries =
        organization.value?.prevent_overlapping_time_entries ?? false;
    form.value.employees_can_manage_tasks = organization.value?.employees_can_manage_tasks ?? false;
});

const mutation = useMutation({
    mutationFn: (values: Partial<UpdateOrganizationBody>) => updateOrganization(values),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
});

async function submit() {
    await mutation.mutateAsync({
        prevent_overlapping_time_entries: form.value.prevent_overlapping_time_entries,
        employees_can_manage_tasks: form.value.employees_can_manage_tasks,
    });
}
</script>

<template>
    <FormSection>
        <template #title>Organization Settings</template>
        <template #description>
            Configure various settings for your organization, including time entry and task
            management permissions.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4 space-y-4">
                <Field orientation="horizontal">
                    <Checkbox
                        id="preventOverlappingTimeEntries"
                        v-model:checked="form.prevent_overlapping_time_entries" />
                    <FieldLabel for="preventOverlappingTimeEntries"
                        >Prevent overlapping time entries (new entries only)</FieldLabel
                    >
                </Field>
                <Field orientation="horizontal">
                    <Checkbox
                        id="employeesCanManageTasks"
                        v-model:checked="form.employees_can_manage_tasks" />
                    <FieldLabel for="employeesCanManageTasks"
                        >Allow Employees to manage tasks</FieldLabel
                    >
                </Field>
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="mutation.isPending.value" @click="submit">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
