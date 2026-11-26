<script setup lang="ts">
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useNotificationsStore } from '@/utils/notification';
import { api, type Export } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { ArrowUpOnSquareIcon } from '@heroicons/vue/24/outline';
import { CardTitle } from '@/packages/ui/src';
import Card from '@/Components/Common/Card.vue';
import { useOrganizationStore } from '@/utils/useOrganization';
import ImportLinkButton from '@/Components/Import/ImportLinkButton.vue';
import { formatDateTimeLocalized } from '@/packages/ui/src/utils/time';

const EXPORT_POLL_INTERVAL_MS = 2000;

const showResultModal = ref(false);
const loading = ref(false);
const downloadUrl = ref<string | null>(null);
const exports = ref<Export[]>([]);

const { organization } = storeToRefs(useOrganizationStore());
const { handleApiRequestNotifications, addNotification } = useNotificationsStore();

const exportInProgress = computed(() =>
    exports.value.some((item) => item.status === 'pending' || item.status === 'running')
);

let isUnmounted = false;
onUnmounted(() => {
    isUnmounted = true;
});

onMounted(async () => {
    const organizationId = getCurrentOrganizationId();
    if (organizationId === null) {
        return;
    }
    await loadExports(organizationId);
    // Resume waiting for an export that was started before the page was opened
    const activeExport = exports.value.find(
        (item) => item.status === 'pending' || item.status === 'running'
    );
    if (activeExport) {
        await waitAndDownload(organizationId, activeExport, false);
    }
});

async function loadExports(organizationId: string) {
    exports.value = (
        await api.getExports({
            params: {
                organization: organizationId,
            },
        })
    ).data;
}

function upsertExport(updatedExport: Export) {
    const index = exports.value.findIndex((item) => item.id === updatedExport.id);
    if (index === -1) {
        exports.value = [updatedExport, ...exports.value];
    } else {
        exports.value[index] = updatedExport;
    }
}

async function exportData() {
    const organizationId = getCurrentOrganizationId();
    if (organizationId === null) {
        return;
    }
    loading.value = true;
    try {
        const response = await handleApiRequestNotifications(
            () =>
                api.exportOrganization(undefined, {
                    params: {
                        organization: organizationId,
                    },
                }),
            undefined,
            'Exporting organization data failed.'
        );
        if (response === undefined) {
            return;
        }
        upsertExport(response.data);
        await waitAndDownload(organizationId, response.data, true);
    } finally {
        loading.value = false;
    }
}

/**
 * The export is processed in the background, poll it until it is completed or failed.
 */
async function waitForExport(organizationId: string, currentExport: Export): Promise<Export> {
    while (
        (currentExport.status === 'pending' || currentExport.status === 'running') &&
        !isUnmounted
    ) {
        await new Promise((resolve) => setTimeout(resolve, EXPORT_POLL_INTERVAL_MS));
        currentExport = (
            await api.getExport({
                params: {
                    organization: organizationId,
                    export: currentExport.id,
                },
            })
        ).data;
        upsertExport(currentExport);
    }
    return currentExport;
}

async function waitAndDownload(organizationId: string, startedExport: Export, showModal: boolean) {
    loading.value = true;
    try {
        const finishedExport = await handleApiRequestNotifications(() =>
            waitForExport(organizationId, startedExport)
        );
        if (finishedExport === undefined || isUnmounted) {
            return;
        }
        if (finishedExport.status === 'failed') {
            addNotification(
                'error',
                'Export failed',
                finishedExport.error_message ?? 'The export failed, please try again.'
            );
            return;
        }
        if (finishedExport.status === 'completed') {
            await download(finishedExport, showModal);
        }
    } finally {
        loading.value = false;
    }
}

async function download(completedExport: Export, showModal: boolean = false) {
    const organizationId = getCurrentOrganizationId();
    if (organizationId === null) {
        return;
    }
    const response = await handleApiRequestNotifications(() =>
        api.downloadExport({
            params: {
                organization: organizationId,
                export: completedExport.id,
            },
        })
    );
    if (response === undefined) {
        return;
    }
    downloadUrl.value = response.download_url;
    if (showModal) {
        showResultModal.value = true;
    }
    window.open(response.download_url, '_self')?.focus();
}

function statusLabel(item: Export): string {
    switch (item.status) {
        case 'pending':
            return 'Waiting';
        case 'running':
            return 'In progress';
        case 'completed':
            return item.download_available ? 'Completed' : 'Expired';
        case 'failed':
            return 'Failed';
    }
    return item.status;
}
</script>

<template>
    <DialogModal closeable :show="showResultModal" @close="showResultModal = false">
        <template #title>The export was successful!</template>
        <template #content>
            <div class="pb-6">
                The download should start automatically. If it does not
                <a
                    class="font-semibold text-accent-200 hover:text-accent-300"
                    target="_self"
                    :href="downloadUrl ?? undefined"
                    >click here</a
                >
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="showResultModal = false"> Close </SecondaryButton>
        </template>
    </DialogModal>
    <!-- Same spacing between title and card as the dashboard cards -->
    <div class="flex flex-col gap-1.5">
        <CardTitle title="Export Data" :icon="ArrowUpOnSquareIcon"></CardTitle>
        <Card>
            <div class="py-6 px-4 flex-col items-center flex space-y-5 text-center text-sm">
                <div>
                    The following organization will be exported: <br />
                    <strong class="font-semibold">{{ organization?.name }}</strong>
                </div>
                <PrimaryButton :loading="loading || exportInProgress" @click="exportData"
                    >Export Organization Data
                </PrimaryButton>
                <div class="flex flex-col items-center gap-1">
                    <p class="max-w-md text-text-secondary">
                        Export your solidtime organization data. This will include all clients,
                        projects, tasks, and time entries.
                    </p>
                    <ImportLinkButton
                        href="https://docs.solidtime.io/user-guide/export"
                        label="Read the export docs" />
                </div>
                <div v-if="exportInProgress" class="text-text-secondary">
                    The export is being prepared, the download starts automatically when it is
                    ready.
                </div>
            </div>
        </Card>
        <Card v-if="exports.length > 0" class="mt-1.5">
            <div class="px-4 py-3 sm:px-5 text-sm">
                <div class="font-semibold text-text-primary pb-2">Recent exports</div>
                <ul>
                    <li
                        v-for="item in exports"
                        :key="item.id"
                        class="flex items-center justify-between py-2 border-t first:border-t-0 border-card-background-separator">
                        <div class="text-text-primary">
                            <span v-if="item.created_at">
                                {{
                                    formatDateTimeLocalized(
                                        item.created_at,
                                        organization?.date_format,
                                        organization?.time_format
                                    )
                                }}
                            </span>
                            <span class="pl-3 text-text-tertiary">{{ statusLabel(item) }}</span>
                        </div>
                        <SecondaryButton
                            v-if="item.download_available"
                            size="small"
                            @click="download(item)">
                            Download
                        </SecondaryButton>
                    </li>
                </ul>
            </div>
        </Card>
    </div>
</template>
