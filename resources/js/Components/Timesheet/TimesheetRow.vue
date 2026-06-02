<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import { XMarkIcon } from '@heroicons/vue/16/solid';
import TimesheetCell from './TimesheetCell.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import TimeEntryRowTagDropdown from '@/packages/ui/src/TimeEntry/TimeEntryRowTagDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Task,
    Client,
    Tag,
    Organization,
} from '@/packages/api/src';
import type { TimesheetRow, TimesheetRowKey } from '@/utils/useTimesheetGrid';
import {
    makeCellStatusKey,
    type CellSaveStatus,
} from '@/utils/timesheet/useTimesheetCellMutations';
import { Button } from '@/packages/ui/src/Buttons';

const organization = inject<ComputedRef<Organization>>('organization');

const props = defineProps<{
    row: TimesheetRow;
    weekDays: string[];
    todayDate: string;
    projects: Project[];
    tasks: Task[];
    clients: Client[];
    tags: Tag[];
    currency: string;
    canCreateProject: boolean;
    enableEstimatedTime: boolean;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
    formatDuration: (seconds: number) => string;
    cellStatuses: Record<string, CellSaveStatus>;
    cellPendingSeconds: Record<string, number>;
}>();

const emit = defineEmits<{
    removeRow: [key: TimesheetRowKey];
    cellUpdate: [dayIndex: number, newSeconds: number];
    projectTaskChange: [projectId: string | null, taskId: string | null];
    billableChange: [billable: boolean];
    tagsChange: [tags: string[]];
}>();

const selectedProject = computed({
    get: () => props.row.projectId,
    set: (val) => emit('projectTaskChange', val, selectedTask.value),
});

const selectedTask = computed({
    get: () => props.row.taskId,
    set: (val) => emit('projectTaskChange', selectedProject.value, val),
});

const rowTotalFormatted = computed(() => props.formatDuration(props.row.totalSeconds));

function hasRunningEntry(dayIndex: number): boolean {
    const cell = props.row.cells.get(dayIndex);
    if (!cell) return false;
    return cell.entries.some((e) => e.end === null);
}
</script>

<template>
    <div data-testid="timesheet_row" class="contents group">
        <!-- Project/Task column -->
        <div
            class="flex items-center gap-1 border-t border-default-background-separator bg-default-background pl-4 pr-3 py-2 md:sticky md:left-0 md:z-10">
            <div class="flex-1 min-w-0">
                <TimeTrackerProjectTaskDropdown
                    v-model:project="selectedProject"
                    v-model:task="selectedTask"
                    :projects="projects"
                    :tasks="tasks"
                    :clients="clients"
                    :currency="currency"
                    :can-create-project="canCreateProject"
                    :enable-estimated-time="enableEstimatedTime"
                    :create-project="createProject"
                    :create-client="createClient"
                    :organization-billable-rate="organization?.billable_rate ?? null"
                    :no-project-value="null"
                    variant="ghost"
                    size="sm"
                    class="w-full" />
            </div>
            <div class="flex items-center gap-1 flex-shrink-0 ml-auto">
                <TimeEntryRowTagDropdown
                    :create-tag="createTag"
                    :tags="tags"
                    :model-value="row.tags"
                    @changed="emit('tagsChange', $event)" />
                <BillableToggleButton
                    :model-value="row.billable"
                    size="small"
                    faded
                    @changed="emit('billableChange', $event)" />
            </div>
        </div>

        <!-- Day cells -->
        <TimesheetCell
            v-for="(day, dayIndex) in weekDays"
            :key="day"
            :cell="row.cells.get(dayIndex)"
            :day-index="dayIndex"
            :date="day"
            :is-today="day === todayDate"
            :has-running-entry="hasRunningEntry(dayIndex)"
            :save-status="cellStatuses[makeCellStatusKey(row.key, dayIndex)]"
            :pending-seconds="cellPendingSeconds[makeCellStatusKey(row.key, dayIndex)]"
            @update="(seconds) => emit('cellUpdate', dayIndex, seconds)" />

        <!-- Row total -->
        <div
            data-testid="timesheet_row_total"
            class="flex items-center justify-end border-t border-default-background-separator pl-3 pr-3 py-3 text-sm font-medium text-text-primary">
            {{ rowTotalFormatted }}
        </div>

        <!-- Remove action -->
        <div
            class="flex items-center justify-center border-t border-default-background-separator pr-4 py-3">
            <Button
                variant="ghost"
                size="icon"
                aria-label="Remove row"
                class="h-6 w-6 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                @click="emit('removeRow', row.key)">
                <XMarkIcon class="h-3.5 w-3.5 text-icon-default" />
            </Button>
        </div>
    </div>
</template>
