<script setup lang="ts">
import { computed, watch } from 'vue';
import { storeToRefs } from 'pinia';
import AppLayout from '@/Layouts/AppLayout.vue';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import TimesheetHeader from '@/Components/Timesheet/TimesheetHeader.vue';
import TimesheetGrid from '@/Components/Timesheet/TimesheetGrid.vue';
import TimesheetFooterActions from '@/Components/Timesheet/TimesheetFooterActions.vue';
import RemoveRowDialog from '@/Components/Timesheet/RemoveRowDialog.vue';
import { useTimesheetQuery } from '@/utils/useTimesheetQuery';
import { useTimesheetGrid } from '@/utils/useTimesheetGrid';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useTagsStore } from '@/utils/useTags';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import { useTimesheetWeek } from '@/utils/timesheet/useTimesheetWeek';
import { useTimesheetCellMutations } from '@/utils/timesheet/useTimesheetCellMutations';
import { useTimesheetRowMutations } from '@/utils/timesheet/useTimesheetRowMutations';
import { useTimesheetRowDeletion } from '@/utils/timesheet/useTimesheetRowDeletion';
import { useCopyLastWeek } from '@/utils/timesheet/useCopyLastWeek';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import type { CreateClientBody, CreateProjectBody, Project, Client, Tag } from '@/packages/api/src';

// ── Week state ────────────────────────────────────────────────────
const {
    weekStart,
    weekEnd,
    weekDays,
    weekNumber,
    isCurrentWeek,
    todayDate,
    goToPreviousWeek,
    goToNextWeek,
    goToCurrentWeek,
} = useTimesheetWeek();

// ── Data fetching ─────────────────────────────────────────────────
const { data, isPending } = useTimesheetQuery(weekStart, weekEnd);
const timeEntries = computed(() => data.value?.data ?? []);

const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();
const { tags } = useTagsQuery();
const { now: currentTimerNow } = storeToRefs(useCurrentTimeEntryStore());

const mutations = useTimeEntriesMutations();

// ── Grid computation ──────────────────────────────────────────────
const { rows, dayTotals, grandTotal, addSlot, removeSlot, updateSlot, clearSlots } =
    useTimesheetGrid(timeEntries, weekDays, projects, tasks, currentTimerNow);

// Wipe slots on week navigation so the new week starts fresh — the
// grid's watcher will reseed from the newly fetched entries.
watch(weekStart, () => clearSlots());

// ── Formatters ────────────────────────────────────────────────────
// Pull number/interval format off the org via its query rather than
// inject('organization'), which is undefined during the page's setup
// (AppLayout provides it later in the lifecycle).
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const intervalFormat = computed(() => organization.value?.interval_format ?? 'hours-minutes');
const numberFormat = computed(() => organization.value?.number_format ?? 'point');

function formatDuration(seconds: number): string {
    if (seconds === 0) return '-';
    return formatHumanReadableDuration(seconds, intervalFormat.value, numberFormat.value);
}

const weekTotalFormatted = computed(() =>
    formatHumanReadableDuration(grandTotal.value, intervalFormat.value, numberFormat.value)
);

const weekRangeDisplay = computed(() => {
    const start = weekStart.value;
    const end = start.add(6, 'day');
    return start.month() === end.month()
        ? `${start.format('MMM D')} - ${end.format('D')}`
        : `${start.format('MMM D')} - ${end.format('MMM D')}`;
});

// ── Cell / row mutation handlers ──────────────────────────────────
const { handleCellUpdate, cellStatus, cellPendingSeconds } = useTimesheetCellMutations(
    weekDays,
    timeEntries,
    rows,
    removeSlot
);

const { handleRowIdentityChange, handleAddRow } = useTimesheetRowMutations(
    mutations,
    projects,
    rows,
    addSlot,
    updateSlot,
    removeSlot
);

const {
    showDeleteDialog,
    deleteRowEntryCount,
    deleteRowProjectName,
    requestRemoveRow,
    confirmDeleteRow,
} = useTimesheetRowDeletion(projects, mutations, removeSlot);

function handleRemoveRow(key: string) {
    const row = rows.value.find((r) => r.key === key);
    if (row) requestRemoveRow(row);
}

// ── Copy last week ────────────────────────────────────────────────
const { isCopyingLastWeek, copyLastWeekRows, copyLastWeekWithTime } = useCopyLastWeek(
    weekStart,
    weekDays,
    rows,
    timeEntries,
    addSlot
);

// ── Inline creation helpers (passed to TimesheetRow) ──────────────
async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(body: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

async function createTag(name: string): Promise<Tag | undefined> {
    return await useTagsStore().createTag(name);
}
</script>

<template>
    <AppLayout title="Timesheet" data-testid="timesheet_view">
        <div class="pt-5 lg:pt-8 pb-4 lg:pb-6">
            <TimesheetHeader
                :is-current-week="isCurrentWeek"
                :week-number="weekNumber"
                :week-range-display="weekRangeDisplay"
                :week-total-formatted="weekTotalFormatted"
                @previous="goToPreviousWeek"
                @next="goToNextWeek"
                @current="goToCurrentWeek" />

            <TimesheetGrid
                v-if="!isPending"
                :rows="rows"
                :week-days="weekDays"
                :today-date="todayDate"
                :day-totals="dayTotals"
                :week-total-formatted="weekTotalFormatted"
                :projects="projects"
                :tasks="tasks"
                :clients="clients"
                :tags="tags"
                :currency="getOrganizationCurrencyString()"
                :can-create-project="canCreateProjects()"
                :enable-estimated-time="isAllowedToPerformPremiumAction()"
                :create-project="createProject"
                :create-client="createClient"
                :create-tag="createTag"
                :format-duration="formatDuration"
                :cell-statuses="cellStatus"
                :cell-pending-seconds="cellPendingSeconds"
                @remove-row="handleRemoveRow"
                @cell-update="handleCellUpdate"
                @project-task-change="
                    (row, projectId, taskId) => handleRowIdentityChange(row, { projectId, taskId })
                "
                @billable-change="(row, billable) => handleRowIdentityChange(row, { billable })"
                @tags-change="(row, tags) => handleRowIdentityChange(row, { tags })"
                @add-row="handleAddRow" />

            <TimesheetFooterActions
                v-if="!isPending"
                :busy="isCopyingLastWeek"
                @copy-rows="copyLastWeekRows"
                @copy-with-time="copyLastWeekWithTime" />

            <div v-else class="flex justify-center items-center py-12">
                <LoadingSpinner />
            </div>
        </div>

        <RemoveRowDialog
            v-model:open="showDeleteDialog"
            :entry-count="deleteRowEntryCount"
            :project-name="deleteRowProjectName"
            @confirm="confirmDeleteRow" />
    </AppLayout>
</template>
