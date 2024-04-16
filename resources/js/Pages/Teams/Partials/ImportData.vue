<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import type { Organization } from '@/types/models';
import { computed, onMounted, ref } from 'vue';
import { useNotificationsStore } from '@/utils/notification';
import { api } from '../../../../../openapi.json.client';
import InputLabel from '@/Components/InputLabel.vue';
import { DocumentIcon } from '@heroicons/vue/24/solid';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { ImportReport, ImportType } from '@/utils/api';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps<{
    team: Organization;
}>();

const importTypeOptions = ref<ImportType[]>([]);

const { addNotification } = useNotificationsStore();

onMounted(async () => {
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        importTypeOptions.value = (
            await api.getImporters({
                params: {
                    organization: organizationId,
                },
            })
        ).data;
    }
});

const reportResult = ref<ImportReport>();
const files = ref<FileList | null>(null);

async function importData() {
    if (importType.value === null) {
        addNotification('error', 'Please select the import type');
        return;
    }
    if (files.value?.length !== 1) {
        addNotification(
            'error',
            'Please select the CSV or ZIP file that you want to import'
        );
        return;
    }
    const base64String = await toBase64(files.value[0]);
    const organizationId = getCurrentOrganizationId();
    if (organizationId !== null) {
        reportResult.value = await api.importData(
            {
                type: importType.value.key,
                data: base64String.replace('data:text/csv;base64,', ''),
            },
            {
                params: {
                    organization: organizationId,
                },
            }
        );
        showResultModal.value = true;
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

function updateFiles() {
    files.value = importFile.value?.files ?? null;
}

const currentImporterDescription = computed(() => {
    if (importType.value === null) {
        return '';
    }
    return importType.value.description;
});

const filenames = computed(() => {
    return files.value?.item(0)?.name ?? 'Import File selected';
});

const importType = ref<ImportType | null>(null);

const showResultModal = ref(false);
</script>

<template>
    <DialogModal
        closeable
        :show="showResultModal"
        @close="showResultModal = false">
        <template #title>Import Result</template>
        <template #content>
            <div class="pb-6">
                The import was successful! Here is an overview of the imported
                data:
            </div>

            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-white font-semibold">Clients created:</span>
                {{ reportResult?.report.clients.created }}
            </div>
            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-white font-semibold">Projects created:</span>
                {{ reportResult?.report.projects.created }}
            </div>
            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-white font-semibold">Tasks created:</span>
                {{ reportResult?.report.tasks.created }}
            </div>
            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-white font-semibold"
                    >Time entries created:</span
                >
                {{ reportResult?.report.time_entries.created }}
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="showResultModal = false">
                Close
            </SecondaryButton>
        </template>
    </DialogModal>
    <FormSection>
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
                        <option :value="null" selected disabled>
                            Select an import type to get instructions...
                        </option>
                        <option
                            v-for="importTypeOption in importTypeOptions"
                            :key="importTypeOption.key"
                            :value="importTypeOption">
                            {{ importTypeOption.name }}
                        </option>
                    </select>
                    <div
                        class="py-3 text-white"
                        v-if="currentImporterDescription">
                        <div class="font-semibold text-muted py-1">
                            Instructions:
                        </div>
                        {{ currentImporterDescription }}
                    </div>
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
                                <span v-if="files">{{ filenames }}</span>
                                <span v-else
                                    >Upload a Toggl/Clockify Export</span
                                >
                                <input
                                    ref="importFile"
                                    id="file-upload"
                                    name="file-upload"
                                    v-on:change="updateFiles"
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
