<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import type { Organization } from '@/types/models';
import { ref } from 'vue';
import { useNotificationsStore } from '@/utils/notification';
import { api } from '../../../../../openapi.json.client';
import InputLabel from '@/Components/InputLabel.vue';
import { DocumentIcon } from '@heroicons/vue/24/solid';
import { getCurrentOrganizationId } from '@/utils/useUser';

defineProps<{
    team: Organization;
}>();

type ImportType =
    | 'toggl_time_entries'
    | 'toggl_data_importer'
    | 'clockify_time_entries'
    | 'clockify_projects';

const importTypeOptions: { value: ImportType; label: string }[] = [
    { value: 'toggl_time_entries', label: 'Toggl Time Entries' },
    { value: 'toggl_data_importer', label: 'Toggl Data Importer' },
    { value: 'clockify_time_entries', label: 'Clockify Time Entries' },
    { value: 'clockify_projects', label: 'Clockify Projects' },
];

const { addNotification } = useNotificationsStore();

async function importData() {
    const files = importFile.value?.files ?? [];
    if (importType.value === null) {
        addNotification('error', 'Please select the import type');
        return;
    }
    if (files.length !== 1) {
        addNotification(
            'error',
            'Please select the CSV or ZIP file that you want to import'
        );
        return;
    }
    const base64String = await toBase64(files[0]);
    const organizationId = getCurrentOrganizationId();
    if (organizationId !== null) {
        await api.importData(
            {
                type: importType.value,
                data: base64String.replace('data:text/csv;base64,', ''),
            },
            {
                params: {
                    organization: organizationId,
                },
            }
        );
    }
}

const importFile = ref<HTMLInputElement | null>();

function toBase64(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => {
            if (reader.result instanceof ArrayBuffer) {
                const decoder = new TextDecoder();
                const str = decoder.decode(reader.result);
                return reject(str);
            } else if (reader.result) {
                resolve(reader.result);
            }
        };
        reader.onerror = reject;
    });
}

const importType = ref<ImportType | null>(null);
</script>

<template>
    <FormSection @submitted="importData">
        <template #title> Import Data</template>

        <template #description>
            Import existing data from Toggl or Clockify.
        </template>

        <template #form>
            <!-- Organization Owner Information -->
            <div class="col-span-6">
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="currency" value="Import Type" />
                    <select
                        name="currency"
                        id="currency"
                        v-model="importType"
                        class="mt-1 block w-full border-input-border bg-input-background text-white focus:border-input-border-active rounded-md shadow-sm">
                        <option value="" disabled>Select a currency</option>
                        <option
                            v-for="importTypeOption in importTypeOptions"
                            :key="importTypeOption.value"
                            :value="importTypeOption.value">
                            {{ importTypeOption.label }}
                        </option>
                    </select>
                </div>

                <div
                    class="mt-2 flex justify-center rounded-lg border border-dashed border-white/30 px-6 py-10">
                    <div class="text-center">
                        <DocumentIcon
                            class="mx-auto h-8 w-8 text-muted"
                            aria-hidden="true" />

                        <div class="mt-4 flex text-sm leading-6 text-muted">
                            <label
                                for="file-upload"
                                class="relative cursor-pointer rounded-md bg-gray-900 font-semibold text-white focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 focus-within:ring-offset-gray-900 hover:text-indigo-500">
                                <span>Upload a Toggl/Clockify Export</span>
                                <input
                                    ref="importFile"
                                    id="file-upload"
                                    name="file-upload"
                                    type="file"
                                    class="sr-only" />
                            </label>
                        </div>
                        <p class="text-xs leading-5 text-muted">
                            CSV and ZIP are supported
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <template #actions>
            <PrimaryButton @click="importData">Import Data</PrimaryButton>
        </template>
    </FormSection>
</template>
