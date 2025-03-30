<script setup lang="ts">
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { computed, onMounted, ref } from 'vue';
import { useNotificationsStore } from '@/utils/notification';
import { api } from '@/packages/api/src';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import { DocumentIcon } from '@heroicons/vue/24/solid';
import {
    ArrowDownOnSquareIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';

import { getCurrentOrganizationId } from '@/utils/useUser';
import type { ImportReport, ImportType } from '@/packages/api/src';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { initializeStores } from '@/utils/init';
import { CardTitle } from '@/packages/ui/src';
import Card from '@/Components/Common/Card.vue';

const importTypeOptions = ref<ImportType[]>([]);

const { addNotification } = useNotificationsStore();

const loading = ref(false);

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

const reportResult = ref<ImportReport | null>();
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
    const rawBase64String = await toBase64(files.value[0]);
    const base64String = rawBase64String
        .split(';')[1]
        .replace('base64,', '') as string;
    const organizationId = getCurrentOrganizationId();
    if (organizationId !== null) {
        const { handleApiRequestNotifications } = useNotificationsStore();
        loading.value = true;
        try {
            reportResult.value = await handleApiRequestNotifications(() => {
                if (importType.value) {
                    return api.importData(
                        {
                            type: importType.value.key,
                            data: base64String,
                        },
                        {
                            params: {
                                organization: organizationId,
                            },
                        }
                    );
                }
                return new Promise((resolve, reject) => {
                    reject('Import type is null');
                });
            });
            initializeStores();
            if (reportResult.value) {
                showResultModal.value = true;
            }
        } finally {
            loading.value = false;
        }
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
                <span class="text-text-primary font-semibold">Clients created:</span>
                {{ reportResult?.report.clients.created }}
            </div>
            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-text-primary font-semibold">Projects created:</span>
                {{ reportResult?.report.projects.created }}
            </div>
            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-text-primary font-semibold">Tasks created:</span>
                {{ reportResult?.report.tasks.created }}
            </div>
            <div
                class="py-2.5 px-3 border-t border-t-card-background-separator">
                <span class="text-text-primary font-semibold"
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
    <div>
        <CardTitle
            title="Import Data"
            :icon="ArrowDownOnSquareIcon"></CardTitle>
        <Card class="mb-3">
            <div class="py-2 px-3 sm:px-4 text-sm flex items-center space-x-3">
                <InformationCircleIcon
                    class="h-5 min-w-0 w-5 text-bg-tertiary" />
                <p class="flex-1">
                    Import existing data from Toggl, Clockify or a different
                    solidtime instance. Please select the type of data you want
                    to import and follow the instructions.
                </p>
            </div>
        </Card>

        <Card>
            <div
                class="px-4 py-5 sm:px-5">
                <div>
                    <InputLabel for="importType" value="Import Type" />
                    <select
                        id="importType"
                        v-model="importType"
                        name="importType"
                        class="mt-1 block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
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
                        v-if="currentImporterDescription"
                        class="py-3 text-text-primary">
                        <div class="font-semibold text-muted py-1">
                            Instructions:
                        </div>
                        <div
                            class="max-w-2xl"
                            v-html="currentImporterDescription"></div>
                    </div>
                </div>

                <div
                    class="mt-2 flex justify-center rounded-lg border border-dashed border-border-primary px-6 py-10">
                    <div class="text-center">
                        <DocumentIcon
                            class="mx-auto h-8 w-8 text-muted"
                            aria-hidden="true" />

                        <div class="mt-4 flex text-sm leading-6 text-muted">
                            <label
                                for="file-upload"
                                class="relative cursor-pointer rounded-md font-semibold text-text-primary focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 focus-within:ring-offset-gray-900 hover:text-indigo-500">
                                <span v-if="files">{{ filenames }}</span>
                                <span v-else
                                    >Upload a Toggl/Clockify Export</span
                                >
                                <input
                                    id="file-upload"
                                    ref="importFile"
                                    name="file-upload"
                                    type="file"
                                    class="sr-only"
                                    @change="updateFiles" />
                            </label>
                        </div>
                        <p class="text-xs leading-5 text-muted">
                            CSV and ZIP are supported
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center justify-end px-4 py-3 bg-card-background border-t border-card-background-separator text-end sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                <PrimaryButton :loading @click="importData"
                    >Import Data
                </PrimaryButton>
            </div>
        </Card>
    </div>
</template>
