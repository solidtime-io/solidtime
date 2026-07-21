<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useFocus } from '@vueuse/core';
import { autoUpdate, flip, limitShift, offset, shift, useFloating } from '@floating-ui/vue';
import TimeTrackerRecentlyTrackedEntry from '@/packages/ui/src/TimeTracker/TimeTrackerRecentlyTrackedEntry.vue';
import { useSelectEvents } from '@/packages/ui/src/utils/select';
import type { Project, Task, TimeEntry } from '@/packages/api/src';

const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', { required: true });

const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    isActive: boolean;
}>();

const emit = defineEmits<{ startTimer: []; updateTimeEntry: [] }>();

const currentTimeEntryDescriptionInput = ref<HTMLInputElement | null>(null);

const tempDescription = ref(currentTimeEntry.value.description);
watch(
    () => currentTimeEntry.value.description,
    () => {
        tempDescription.value = currentTimeEntry.value.description;
    }
);

function updateTimeEntryDescription() {
    if (currentTimeEntry.value.description !== tempDescription.value) {
        currentTimeEntry.value.description = tempDescription.value;
        emit('updateTimeEntry');
    }
}

function setCurrentTimeEntry(timeEntry: TimeEntry) {
    currentTimeEntry.value.description = timeEntry.description;
    currentTimeEntry.value.project_id = timeEntry.project_id;
    currentTimeEntry.value.task_id = timeEntry.task_id;
    currentTimeEntry.value.tags = timeEntry.tags;
    currentTimeEntry.value.billable = timeEntry.billable;
    currentTimeEntry.value.type = timeEntry.type;
}

function setAndStartTimer(timeEntry: TimeEntry) {
    setCurrentTimeEntry(timeEntry);
    if (!props.isActive) {
        emit('startTimer');
    } else {
        emit('updateTimeEntry');
    }
}

// Starts the timer from the description input / range selector Enter: picks the highlighted
// recently-tracked entry if one is active, otherwise commits the typed description.
function submit() {
    if (highlightedDropdownEntryId.value) {
        const timeEntry = filteredRecentlyTrackedTimeEntries.value.find(
            (item) => item.id === highlightedDropdownEntryId.value
        );
        if (timeEntry) {
            setCurrentTimeEntry(timeEntry);
            showDropdown.value = false;
        }
    } else {
        currentTimeEntry.value.description = tempDescription.value;
    }

    if (!props.isActive) {
        emit('startTimer');
    } else {
        emit('updateTimeEntry');
    }
}

const filteredRecentlyTrackedTimeEntries = computed(() => {
    // do not include running time entries and breaks (breaks are started via the break button)
    const finishedTimeEntries = props.timeEntries.filter(
        (item) => item.end !== null && item.type !== 'break'
    );

    // filter out duplicates based on description, task, project, tags and billable
    const nonDuplicateTimeEntries = finishedTimeEntries.filter((item, index, self) => {
        return (
            index ===
            self.findIndex(
                (t) =>
                    t.description === item.description &&
                    t.task_id === item.task_id &&
                    t.project_id === item.project_id &&
                    t.tags.length === item.tags.length &&
                    t.tags.every((tag) => item.tags.includes(tag)) &&
                    t.billable === item.billable
            )
        );
    });

    // filter time entries based on current description
    return nonDuplicateTimeEntries
        .filter((item) => {
            return item.description
                ?.toLowerCase()
                ?.includes(tempDescription.value?.toLowerCase()?.trim() || '');
        })
        .slice(0, 5);
});

const showDropdown = ref(false);
const blockRefocus = ref(false);
const { focused } = useFocus(currentTimeEntryDescriptionInput);

watch(focused, (focused) => {
    nextTick(() => {
        // make sure the click event on the dropdown does not get interrupted
        showDropdown.value = focused;

        // make sure that the input does not get refocused after the dropdown is closed
        if (!focused) {
            blockRefocus.value = true;
            setTimeout(() => {
                blockRefocus.value = false;
            }, 100);
        }
    });
});

const floating = ref(null);
const { floatingStyles } = useFloating(currentTimeEntryDescriptionInput, floating, {
    placement: 'bottom-start',
    whileElementsMounted: autoUpdate,
    middleware: [
        offset(10),
        shift({
            limiter: limitShift({
                offset: 5,
            }),
        }),
        flip({
            fallbackAxisSideDirection: 'start',
        }),
    ],
});
const highlightedDropdownEntryId = ref<string | null>(null);

useSelectEvents(
    filteredRecentlyTrackedTimeEntries,
    highlightedDropdownEntryId,
    (item) => item.id,
    showDropdown
);

// Called by the shell after the start/stop button starts a timer, so typing can continue.
function focusAfterStart() {
    if (!blockRefocus.value) {
        currentTimeEntryDescriptionInput.value?.focus();
    }
}

defineExpose({ submit, focusAfterStart });
</script>

<template>
    <input
        ref="currentTimeEntryDescriptionInput"
        v-model="tempDescription"
        placeholder="What are you working on?"
        data-testid="time_entry_description"
        class="w-full rounded-l-lg py-4 sm:py-2.5 px-3.5 border-b border-b-card-background-separator @2xl:px-4 text-base text-text-primary bg-transparent border-none placeholder-text-secondary focus:ring-0 transition"
        type="text"
        @keydown.enter="submit"
        @keydown.esc="showDropdown = false"
        @blur="updateTimeEntryDescription" />
    <div
        v-if="showDropdown && filteredRecentlyTrackedTimeEntries.length > 0"
        ref="floating"
        class="z-50 w-[min(640px,100vw-2rem)]"
        :style="floatingStyles">
        <div
            class="rounded-lg w-full border border-card-border overflow-hidden shadow-dropdown bg-card-background">
            <div
                class="text-text-tertiary text-xs font-semibold border-b border-border-tertiary px-2 py-1.5">
                Recently Tracked Time Entries
            </div>
            <div class="text-text-secondary py-1 px-1.5">
                <TimeTrackerRecentlyTrackedEntry
                    v-for="timeEntry in filteredRecentlyTrackedTimeEntries"
                    :key="timeEntry.id"
                    :time-entry="timeEntry"
                    :highlighted="highlightedDropdownEntryId === timeEntry.id"
                    :projects="projects"
                    :tasks="tasks"
                    @mousedown="setAndStartTimer(timeEntry)"
                    @mouseenter="
                        highlightedDropdownEntryId = timeEntry.id
                    "></TimeTrackerRecentlyTrackedEntry>
            </div>
        </div>
    </div>
</template>
