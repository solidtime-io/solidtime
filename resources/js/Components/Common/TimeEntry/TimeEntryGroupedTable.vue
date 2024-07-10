<script setup lang="ts">
import { computed } from 'vue';
import type { Project, Task, TimeEntry } from '@/utils/api';
import { getDayJsInstance, getLocalizedDateFromTimestamp } from '@/utils/time';
import type { TimeEntriesGroupedByType } from '@/utils/useTimeEntries';
import TimeEntryAggregateRow from '@/Components/Common/TimeEntry/TimeEntryAggregateRow.vue';
import TimeEntryRowHeading from '@/Components/Common/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';

const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
}>();

const emit = defineEmits<{
    updateTimeEntry: [entry: TimeEntry];
    updateTimeEntries: [entries: TimeEntry[]];
    deleteTimeEntries: [entries: TimeEntry[]];
    onStartStopClick: [entry: TimeEntry];
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
</script>

<template>
    <div v-for="(value, key) in groupedTimeEntries" :key="key">
        <TimeEntryRowHeading :date="key"></TimeEntryRowHeading>
        <template v-for="entry in value" :key="entry.id">
            <TimeEntryAggregateRow
                :projects="projects"
                :tasks="tasks"
                @onStartStopClick="(arg) => emit('onStartStopClick', arg)"
                @updateTimeEntries="(arg) => emit('updateTimeEntries', arg)"
                @deleteTimeEntries="(arg) => emit('deleteTimeEntries', arg)"
                v-if="'timeEntries' in entry && entry.timeEntries.length > 1"
                :time-entry="entry"></TimeEntryAggregateRow>
            <TimeEntryRow
                :projects="projects"
                :tasks="tasks"
                @updateTimeEntry="(arg) => emit('updateTimeEntry', arg)"
                @onStartStopClick="() => emit('onStartStopClick', entry)"
                @deleteTimeEntry="() => emit('deleteTimeEntries', [entry])"
                v-else
                :time-entry="entry"></TimeEntryRow>
        </template>
    </div>
</template>

<style scoped></style>
