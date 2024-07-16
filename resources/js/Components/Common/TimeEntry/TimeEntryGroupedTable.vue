<script setup lang="ts">
import { computed } from 'vue';
import type {
    CreateTimeEntryBody,
    Project,
    Tag,
    Task,
    TimeEntry,
} from '@/utils/api';
import { getDayJsInstance, getLocalizedDateFromTimestamp } from '@/utils/time';
import type { TimeEntriesGroupedByType } from '@/utils/useTimeEntries';
import TimeEntryAggregateRow from '@/Components/Common/TimeEntry/TimeEntryAggregateRow.vue';
import TimeEntryRowHeading from '@/Components/Common/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';
import dayjs from 'dayjs';

const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    createTag: (name: string) => Promise<Tag | undefined>;
    updateTimeEntry: (entry: TimeEntry) => void;
    updateTimeEntries: (entries: TimeEntry[]) => void;
    deleteTimeEntries: (entries: TimeEntry[]) => void;
    createTimeEntry: (entry: Omit<CreateTimeEntryBody, 'member_id'>) => void;
}>();

const groupedTimeEntries = computed(() => {
    const groupedEntriesByDay: Record<string, TimeEntry[]> = {};
    for (const entry of props.timeEntries) {
        // skip current time entry
        if (entry.end === null) {
            continue;
        }
        const oldEntries =
            groupedEntriesByDay[getLocalizedDateFromTimestamp(entry.start)];
        groupedEntriesByDay[getLocalizedDateFromTimestamp(entry.start)] = [
            ...(oldEntries ?? []),
            entry,
        ];
    }
    const groupedEntriesByDayAndType: Record<
        string,
        TimeEntriesGroupedByType[]
    > = {};
    for (const dailyEntriesKey in groupedEntriesByDay) {
        const dailyEntries = groupedEntriesByDay[dailyEntriesKey];
        const newDailyEntries: TimeEntriesGroupedByType[] = [];

        for (const entry of dailyEntries) {
            // check if same entry already exists
            const oldEntriesIndex = newDailyEntries.findIndex(
                (e) =>
                    e.project_id === entry.project_id &&
                    e.task_id === entry.task_id &&
                    e.billable === entry.billable &&
                    e.description === entry.description
            );
            if (oldEntriesIndex !== -1 && newDailyEntries[oldEntriesIndex]) {
                newDailyEntries[oldEntriesIndex].timeEntries.push(entry);

                // Add up durations for time entries of the same type
                newDailyEntries[oldEntriesIndex].duration =
                    (newDailyEntries[oldEntriesIndex].duration ?? 0) +
                    (entry?.duration ?? 0);

                // adapt start end times so they show the earliest start and latest end time
                if (
                    getDayJsInstance()(entry.start).isBefore(
                        getDayJsInstance()(
                            newDailyEntries[oldEntriesIndex].start
                        )
                    )
                ) {
                    newDailyEntries[oldEntriesIndex].start = entry.start;
                }
                if (
                    getDayJsInstance()(entry.end).isAfter(
                        getDayJsInstance()(newDailyEntries[oldEntriesIndex].end)
                    )
                ) {
                    newDailyEntries[oldEntriesIndex].end = entry.end;
                }
            } else {
                newDailyEntries.push({ ...entry, timeEntries: [entry] });
            }
        }

        groupedEntriesByDayAndType[dailyEntriesKey] = newDailyEntries;
    }
    return groupedEntriesByDayAndType;
});

function startTimeEntryFromExisting(entry: TimeEntry) {
    props.createTimeEntry({
        project_id: entry.project_id,
        task_id: entry.task_id,
        start: dayjs().utc().format(),
        end: null,
        billable: entry.billable,
        description: entry.description,
    });
}
</script>

<template>
    <div v-for="(value, key) in groupedTimeEntries" :key="key">
        <TimeEntryRowHeading :date="key"></TimeEntryRowHeading>
        <template v-for="entry in value" :key="entry.id">
            <TimeEntryAggregateRow
                :projects="projects"
                :tasks="tasks"
                :tags="tags"
                :onStartStopClick="startTimeEntryFromExisting"
                :updateTimeEntries
                :deleteTimeEntries
                :createTag
                v-if="'timeEntries' in entry && entry.timeEntries.length > 1"
                :time-entry="entry"></TimeEntryAggregateRow>
            <TimeEntryRow
                :projects="projects"
                :tasks="tasks"
                :tags="tags"
                :createTag
                :updateTimeEntry
                :onStartStopClick="() => startTimeEntryFromExisting(entry)"
                :deleteTimeEntry="() => deleteTimeEntries([entry])"
                v-else
                :time-entry="entry"></TimeEntryRow>
        </template>
    </div>
</template>

<style scoped></style>
