<script setup lang="ts">
import { ref } from 'vue';
import TableRow from '@/Components/TableRow.vue';
import { api, type Report } from '@/packages/api/src';
import ReportMoreOptionsDropdown from '@/Components/Common/Report/ReportMoreOptionsDropdown.vue';
import ReportEditModal from '@/Components/Common/Report/ReportEditModal.vue';
import { SecondaryButton } from '@/packages/ui/src';
import { useClipboard } from '@vueuse/core';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/solid';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

const props = defineProps<{
    report: Report;
}>();

const showEditReportModal = ref(false);

const { copy, copied, isSupported } = useClipboard({ legacy: true });
const { handleApiRequestNotifications } = useNotificationsStore();

function openSharableLink() {
    const link = props.report.shareable_link;
    if (link) {
        window.open(link, '_blank')?.focus();
    }
}

const queryClient = useQueryClient();
const deleteReportMutation = useMutation({
    mutationFn: async (reportId: string) => {
        const organizationId = getCurrentOrganizationId();
        if (organizationId === null) {
            throw new Error('No current organization id - update report');
        }
        return await api.deleteReport(undefined, {
            params: {
                organization: organizationId,
                report: reportId,
            },
        });
    },
    onSuccess: () => {
        queryClient.invalidateQueries({
            queryKey: ['reports'],
        });
    },
});
async function deleteReport() {
    await handleApiRequestNotifications(
        () => deleteReportMutation.mutateAsync(props.report.id),
        'Success',
        'Error'
    );
}
</script>

<template>
    <ReportEditModal
        v-model:show="showEditReportModal"
        :original-report="report"></ReportEditModal>
    <TableRow>
        <div
            class="whitespace-nowrap min-w-0 flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span class="overflow-ellipsis overflow-hidden">
                {{ report.name }}
            </span>
        </div>
        <div class="whitespace-nowrap min-w-0 px-3 py-4 text-sm text-text-secondary">
            <span class="overflow-ellipsis overflow-hidden">
                {{ report.description }}
            </span>
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{ report.is_public ? 'Public' : 'Private' }}
        </div>
        <div
            class="whitespace-nowrap px-3 flex items-center text-sm text-text-secondary">
            <div
                v-if="report.shareable_link"
                class="space-x-2 flex items-center">
                <SecondaryButton
                    v-if="isSupported"
                    @click="copy(report.shareable_link)">
                    <span v-if="!copied">Copy URL</span>
                    <span v-else>Copied!</span>
                </SecondaryButton>
                <button
                    class="outline-0 focus-visible:ring-2 w-6 h-6 flex items-center justify-center rounded focus-visible:ring-ring"
                    @click="openSharableLink">
                    <ArrowTopRightOnSquareIcon
                        class="w-4 text-text-tertiary hover:text-text-secondary transition"></ArrowTopRightOnSquareIcon>
                </button>
            </div>
            <span v-else> -- </span>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <ReportMoreOptionsDropdown
                :report="report"
                @edit="showEditReportModal = true"
                @delete="deleteReport"></ReportMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
