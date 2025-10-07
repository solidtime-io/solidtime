<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import { Checkbox } from '@/packages/ui/src';
import type { UpdateOrganizationBody } from '@/packages/api/src';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import { useMutation, useQueryClient } from '@tanstack/vue-query';

const store = useOrganizationStore();
const { updateOrganization } = store;
const { organization } = storeToRefs(store);
const queryClient = useQueryClient();

const form = ref<{ prevent_overlapping_time_entries: boolean }>({
    prevent_overlapping_time_entries: false,
});

onMounted(async () => {
    form.value.prevent_overlapping_time_entries =
        organization.value?.prevent_overlapping_time_entries ?? false;
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
    });
}
</script>

<template>
    <FormSection>
        <template #title>Time Entry Settings</template>
        <template #description>
            Disallow overlapping time entries for members of this organization. When enabled, users
            cannot create new time entries that overlap with their existing ones. This only affects
            newly created entries.
        </template>

        <template #form>
            <div class="col-span-6">
                <div class="col-span-6 sm:col-span-4">
                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="preventOverlappingTimeEntries"
                            v-model:checked="form.prevent_overlapping_time_entries" />
                        <InputLabel
                            for="preventOverlappingTimeEntries"
                            value="Prevent overlapping time entries (new entries only)" />
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="mutation.isPending.value" @click="submit">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
