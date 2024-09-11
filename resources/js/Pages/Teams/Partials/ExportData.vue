<script setup lang="ts">
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { ref } from 'vue';
import { useNotificationsStore } from '@/utils/notification';
import { api, type OrganizationExportResponse } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import {
    ArrowUpOnSquareIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';
import { CardTitle } from '@/packages/ui/src';
import Card from '@/Components/Common/Card.vue';
import { useOrganizationStore } from '@/utils/useOrganization';

const showResultModal = ref(false);
const loading = ref(false);
const exportResponse = ref<OrganizationExportResponse | null>(null);

const { organization } = useOrganizationStore();
const { handleApiRequestNotifications } = useNotificationsStore();

async function exportData() {
    loading.value = true;
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        const response = await handleApiRequestNotifications(
            () =>
                api.exportOrganization(
                    {},
                    {
                        params: {
                            organization: organizationId,
                        },
                    }
                ),
            'Organization data exported successfully.',
            'Exporting organization data failed.'
        );
        if (response) {
            showResultModal.value = true;
            loading.value = false;
            exportResponse.value = response;
            window.open(response.download_url, '_blank')?.focus();
        }
    }
}
</script>

<template>
    <DialogModal
        closeable
        :show="showResultModal"
        @close="showResultModal = false">
        <template #title>The export was successful!</template>
        <template #content>
            <div class="pb-6">
                The download should start automatically. If it does not
                <a
                    class="font-semibold text-accent-200 hover:text-accent-300"
                    target="_blank"
                    :href="exportResponse?.download_url"
                    >click here</a
                >
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="showResultModal = false">
                Close
            </SecondaryButton>
        </template>
    </DialogModal>
    <div>
        <CardTitle title="Export Data" :icon="ArrowUpOnSquareIcon"></CardTitle>
        <Card class="mb-3">
            <div class="py-2 px-3 sm:px-4 text-sm flex items-center space-x-3">
                <InformationCircleIcon
                    class="h-5 min-w-0 w-5 text-bg-tertiary" />
                <p class="flex-1">
                    Export your solidtime organization data. This will include
                    all clients, projects, tasks, and time entries. You will
                    receive a zip file with json files for each entity.
                </p>
            </div>
        </Card>
        <Card>
            <div
                class="py-6 flex-col bg-card-background items-center flex space-y-5 text-center text-sm">
                <div>
                    The following organization will be exported: <br />
                    <strong class="font-semibold">{{
                        organization?.name
                    }}</strong>
                </div>
                <PrimaryButton :loading @click="exportData"
                    >Export Organization Data
                </PrimaryButton>
            </div>
        </Card>
    </div>
</template>
