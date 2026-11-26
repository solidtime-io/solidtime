<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { ArrowDownOnSquareIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { canUseBillableRates } from '@/utils/billing';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Import } from '@/packages/api/src';
import { CardTitle } from '@/packages/ui/src';
import Card from '@/Components/Common/Card.vue';
import { formatDateTimeLocalized } from '@/packages/ui/src/utils/time';
import { useOrganizationStore } from '@/utils/useOrganization';
import ImportWizard from '@/Components/Import/ImportWizard.vue';
import { useImports } from '@/Components/Import/useImports';
import type { ImportSourceId } from '@/Components/Import/importSources';

const organizationId = getCurrentOrganizationId();
const importsState = useImports(organizationId ?? '');
const { imports, importers } = importsState;

const { organization } = storeToRefs(useOrganizationStore());

// Recent imports are only shown while no source is open in the wizard
const openSource = ref<ImportSourceId | null>(null);

const billableRatesLocked = computed(() => !canUseBillableRates());

onMounted(async () => {
    if (organizationId !== null) {
        await importsState.load();
    }
});

function importTypeName(type: string): string {
    return importers.value.find((importer) => importer.key === type)?.name ?? type;
}

function statusLabel(item: Import): string {
    switch (item.status) {
        case 'pending':
            return 'Waiting';
        case 'running':
            return 'In progress';
        case 'completed':
            return 'Completed';
        case 'failed':
            return 'Failed';
    }
    return item.status;
}
</script>

<template>
    <!-- Same spacing between title and card as the dashboard cards -->
    <div class="flex flex-col gap-1.5">
        <CardTitle title="Import Data" :icon="ArrowDownOnSquareIcon"></CardTitle>
        <div v-if="billableRatesLocked" class="mb-1.5 text-sm text-text-secondary">
            <p class="flex items-start space-x-2">
                <ExclamationTriangleIcon class="h-5 min-w-0 w-5 text-yellow-500 shrink-0" />
                <span>
                    Billable rates and billable status will not be imported, since they are not
                    available in the free plan. All projects and time entries will be imported as
                    non-billable.
                </span>
            </p>
        </div>

        <Card>
            <ImportWizard v-model:source="openSource" :state="importsState" />
        </Card>

        <Card v-if="openSource === null && imports.length > 0" class="mt-1.5">
            <div class="px-4 py-3 sm:px-5 text-sm" data-testid="recent_imports">
                <div class="font-semibold text-text-primary pb-2">Recent imports</div>
                <ul>
                    <li
                        v-for="item in imports"
                        :key="item.id"
                        class="py-2 border-t first:border-t-0 border-card-background-separator">
                        <div class="flex items-center justify-between space-x-3">
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
                                <span class="pl-3">{{ importTypeName(item.type) }}</span>
                            </div>
                            <span
                                :class="[
                                    item.status === 'failed'
                                        ? 'text-red-500'
                                        : 'text-text-tertiary',
                                    'whitespace-nowrap',
                                ]"
                                >{{ statusLabel(item) }}</span
                            >
                        </div>
                        <div
                            v-if="item.status === 'completed' && item.report"
                            class="text-text-secondary text-xs pt-1">
                            {{ item.report.time_entries.created }} time entries,
                            {{ item.report.projects.created }} projects,
                            {{ item.report.clients.created }} clients,
                            {{ item.report.tasks.created }} tasks created
                        </div>
                        <div
                            v-else-if="item.status === 'failed' && item.error_message"
                            class="text-text-secondary text-xs pt-1">
                            {{ item.error_message }}
                        </div>
                    </li>
                </ul>
            </div>
        </Card>
    </div>
</template>
