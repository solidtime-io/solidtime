<script setup lang="ts">
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import type { DatesSetArg, EventClickArg, EventDropArg, EventChangeArg } from '@fullcalendar/core';
import {
    computed,
    ref,
    watch,
    inject,
    type ComputedRef,
    nextTick,
    onMounted,
    onActivated,
} from 'vue';
import chroma from 'chroma-js';
import { useCssVariable } from '@/utils/useCssVariable';
import { getDayJsInstance, getLocalizedDayJs } from '../utils/time';
import { getUserTimezone, getWeekStart } from '../utils/settings';
import { LoadingSpinner, TimeEntryCreateModal, TimeEntryEditModal } from '..';
import FullCalendarEventContent from './FullCalendarEventContent.vue';
import FullCalendarDayHeader from './FullCalendarDayHeader.vue';
import activityStatusPlugin, {
    type ActivityPeriod,
    renderActivityStatusBoxes,
} from './idleStatusPlugin';
import type {
    TimeEntry,
    Project,
    Client,
    Task,
    CreateProjectBody,
    CreateClientBody,
    Tag,
    Organization,
} from '@/packages/api/src';
import type { Dayjs } from 'dayjs';

type CalendarExtendedProps = { timeEntry: TimeEntry } & Record<string, unknown>;

const emit = defineEmits<{
    (e: 'dates-change', payload: { start: Date; end: Date }): void;
    (e: 'refresh'): void;
}>();

const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    clients: Client[];
    tags: Tag[];
    activityPeriods?: ActivityPeriod[];
    loading?: boolean;

    // Permissions / feature flags
    enableEstimatedTime: boolean;
    currency: string;
    canCreateProject: boolean;

    createTimeEntry: (
        entry: Omit<TimeEntry, 'id' | 'organization_id' | 'user_id'>
    ) => Promise<void>;
    updateTimeEntry: (entry: TimeEntry) => Promise<void>;
    deleteTimeEntry: (timeEntryId: string) => Promise<void>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
}>();

// Local component state
const newEventStart = ref<Dayjs | null>(null);
const newEventEnd = ref<Dayjs | null>(null);
const showCreateTimeEntryModal = ref<boolean>(false);
const showEditTimeEntryModal = ref<boolean>(false);
const selectedTimeEntry = ref<TimeEntry | null>(null);

const calendarRef = ref<InstanceType<typeof FullCalendar> | null>(null);

// Inject organization data for settings
const organization = inject<ComputedRef<Organization>>('organization');

// Helper function to convert week start to FullCalendar firstDay value
const getFirstDay = () => {
    const weekStart = getWeekStart();
    const weekStartMap: Record<string, number> = {
        'sunday': 0,
        'monday': 1,
        'tuesday': 2,
        'wednesday': 3,
        'thursday': 4,
        'friday': 5,
        'saturday': 6,
    };
    return weekStartMap[weekStart] ?? 1; // Default to Monday if not found
};

// Helper function to get time format for slot labels
const getSlotLabelFormat = () => {
    const timeFormat = organization?.value?.time_format || '24-hours';
    if (timeFormat === '12-hours') {
        return {
            hour: 'numeric' as const,
            hour12: true,
        };
    } else {
        return {
            hour: '2-digit' as const,
            minute: '2-digit' as const,
            hour12: false,
        };
    }
};

const cssBackground = useCssVariable('--color-bg-background');

const events = computed(() => {
    const themeBackground = (() => {
        return cssBackground.value?.trim();
    })();
    return props.timeEntries
        ?.filter((timeEntry) => timeEntry.end !== null)
        ?.map((timeEntry) => {
            const project = props.projects.find((p) => p.id === timeEntry.project_id);
            const client = props.clients.find((c) => c.id === project?.client_id);
            const task = props.tasks.find((t) => t.id === timeEntry.task_id);
            const duration = getDayJsInstance()(timeEntry.end!).diff(
                getDayJsInstance()(timeEntry.start),
                'minutes'
            );

            const title = timeEntry.description || 'No description';

            const baseColor = project?.color || '#6B7280';
            const backgroundColor = chroma.mix(baseColor, themeBackground, 0.65, 'lab').hex();
            const borderColor = chroma.mix(baseColor, themeBackground, 0.5, 'lab').hex();

            // For 0-duration events, display them with minimum visual duration but preserve actual duration
            const startTime = getLocalizedDayJs(timeEntry.start);
            const endTime =
                duration === 0
                    ? startTime.add(1, 'second') // Show as 1 second for minimal visibility
                    : getLocalizedDayJs(timeEntry.end!);

            return {
                id: timeEntry.id,
                start: startTime.format(),
                end: endTime.format(),
                title,
                backgroundColor,
                borderColor,
                textColor: 'var(--foreground)',
                extendedProps: {
                    timeEntry,
                    project,
                    client,
                    task,
                    duration,
                },
            };
        });
});

// Daily totals used in day header
const dailyTotals = computed(() => {
    const totals: Record<string, number> = {};
    props.timeEntries
        .filter((entry) => entry.end !== null)
        .forEach((entry) => {
            const date = getDayJsInstance()(entry.start).format('YYYY-MM-DD');
            const duration = getDayJsInstance()(entry.end!).diff(
                getDayJsInstance()(entry.start),
                'minutes'
            );
            totals[date] = (totals[date] || 0) + duration;
        });
    return totals;
});

function emitDatesChange(arg: DatesSetArg) {
    emit('dates-change', { start: arg.start, end: arg.end });
    // Render activity boxes after calendar view has been rendered
    renderActivityBoxes();
}

function handleDateSelect(arg: { start: Date; end: Date }) {
    const startTime = getDayJsInstance()(arg.start.toISOString())
        .utc()
        .tz(getUserTimezone(), true)
        .utc();
    const endTime = getDayJsInstance()(arg.end.toISOString())
        .utc()
        .tz(getUserTimezone(), true)
        .utc();
    newEventStart.value = startTime;
    newEventEnd.value = endTime;
    showCreateTimeEntryModal.value = true;
}

function handleEventClick(arg: EventClickArg) {
    const ext = arg.event.extendedProps as CalendarExtendedProps;
    selectedTimeEntry.value = ext.timeEntry;
    showEditTimeEntryModal.value = true;
}

async function handleEventDrop(arg: EventDropArg) {
    const ext = arg.event.extendedProps as CalendarExtendedProps;
    const timeEntry = ext.timeEntry;
    if (!arg.event.start || !arg.event.end) return;
    const updatedTimeEntry = {
        ...timeEntry,
        start: getDayJsInstance()(arg.event.start.toISOString())
            .utc()
            .tz(getUserTimezone(), true)
            .second(0)
            .utc()
            .format(),
        end: getDayJsInstance()(arg.event.end.toISOString())
            .utc()
            .tz(getUserTimezone(), true)
            .second(0)
            .utc()
            .format(),
    } as TimeEntry;
    await props.updateTimeEntry(updatedTimeEntry);
    emit('refresh');
}

async function handleEventResize(arg: EventChangeArg) {
    const ext = arg.event.extendedProps as CalendarExtendedProps;
    const timeEntry = ext.timeEntry;
    if (!arg.event.start || !arg.event.end) return;
    const updatedTimeEntry = {
        ...timeEntry,
        start: getDayJsInstance()(arg.event.start.toISOString())
            .utc()
            .tz(getUserTimezone(), true)
            .second(0)
            .utc()
            .format(),
        end: getDayJsInstance()(arg.event.end.toISOString())
            .utc()
            .tz(getUserTimezone(), true)
            .second(0)
            .utc()
            .format(),
    } as TimeEntry;
    await props.updateTimeEntry(updatedTimeEntry);
    emit('refresh');
}

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, activityStatusPlugin],
    initialView: 'timeGridWeek',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'timeGridWeek,timeGridDay',
    },
    height: 'parent',
    slotMinTime: '00:00:00',
    slotMaxTime: '24:00:00',
    slotDuration: '00:15:00',
    slotLabelInterval: '01:00:00',
    slotLabelFormat: getSlotLabelFormat(),
    snapDuration: '00:01:00',
    firstDay: getFirstDay(),
    allDaySlot: false,
    nowIndicator: true,
    selectable: true,
    selectMirror: true,
    editable: true,
    eventResizableFromStart: true,
    eventDurationEditable: true,
    timeZone: getUserTimezone(),
    eventStartEditable: true,
    select: handleDateSelect,
    eventClick: handleEventClick,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    datesSet: emitDatesChange,

    events: events.value,
    activityPeriods: props.activityPeriods || [],
}));

watch(showCreateTimeEntryModal, (value) => {
    if (!value) {
        newEventStart.value = null;
        newEventEnd.value = null;
        // Ensure FullCalendar clears the selection mirror when modal closes
        calendarRef.value?.getApi().unselect();
        emit('refresh');
    }
});

watch(showEditTimeEntryModal, (value) => {
    if (!value) {
        selectedTimeEntry.value = null;
        emit('refresh');
    }
});

// Render activity status boxes after FullCalendar has rendered
const renderActivityBoxes = () => {
    if (!calendarRef.value || !props.activityPeriods) return;

    const calendarEl = calendarRef.value.$el as HTMLElement;
    if (calendarEl && props.activityPeriods.length > 0) {
        renderActivityStatusBoxes(calendarEl, props.activityPeriods);
    }
};

// Watch for activity periods changes - re-render when data changes
watch(
    () => props.activityPeriods,
    () => {
        renderActivityBoxes();
    }
);

const scrollToCurrentTime = () => {
    nextTick(() => {
        if (calendarRef.value) {
            const now = getDayJsInstance()();
            const oneHourBefore = now.subtract(1, 'hour');

            // If subtracting 1 hour keeps us on the same day, scroll to 1 hour before
            const scrollTime = now.isSame(oneHourBefore, 'day')
                ? oneHourBefore.format('HH:mm:ss')
                : now.format('HH:mm:ss');

            calendarRef.value.getApi().scrollToTime(scrollTime);
        }
    });
};

onMounted(() => {
    scrollToCurrentTime();
});

onActivated(() => {
    scrollToCurrentTime();
});
</script>

<template>
    <div class="w-full relative h-full flex-1">
        <div v-if="loading" class="flex items-center justify-center h-full">
            <div class="flex flex-col items-center space-y-4">
                <LoadingSpinner class="h-8 w-8" />
                <p class="text-muted-foreground">Loading calendar data...</p>
            </div>
        </div>

        <TimeEntryCreateModal
            v-model:show="showCreateTimeEntryModal"
            :enable-estimated-time="enableEstimatedTime"
            :create-time-entry="createTimeEntry"
            :create-client="createClient"
            :create-project="createProject"
            :create-tag="createTag"
            :currency="currency"
            :can-create-project="canCreateProject"
            :tags="tags as any"
            :projects="projects"
            :tasks="tasks"
            :clients="clients"
            :start="newEventStart ? newEventStart.toISOString() : undefined"
            :end="newEventEnd ? newEventEnd.toISOString() : undefined" />

        <TimeEntryEditModal
            v-model:show="showEditTimeEntryModal"
            :time-entry="selectedTimeEntry as any"
            :enable-estimated-time="enableEstimatedTime"
            :update-time-entry="updateTimeEntry"
            :delete-time-entry="deleteTimeEntry"
            :create-client="createClient"
            :create-project="createProject"
            :create-tag="createTag"
            :tags="tags as any"
            :projects="projects"
            :tasks="tasks"
            :clients="clients"
            :currency="currency"
            :can-create-project="canCreateProject" />
        <FullCalendar ref="calendarRef" class="fullcalendar" :options="calendarOptions">
            <template #eventContent="arg">
                <FullCalendarEventContent
                    :title="arg.event.title"
                    :project-name="(arg.event.extendedProps as any).project?.name"
                    :task-name="(arg.event.extendedProps as any).task?.name"
                    :client-name="(arg.event.extendedProps as any).client?.name"
                    :duration-seconds="
                        ((arg.event.extendedProps as any).duration ?? undefined)
                            ? (arg.event.extendedProps as any).duration * 60
                            : undefined
                    "
                    :start="arg.event.start as any"
                    :end="arg.event.end as any" />
            </template>
            <template #dayHeaderContent="arg">
                <FullCalendarDayHeader
                    :date="
                        getDayJsInstance()(arg.date.toISOString()).utc().tz(getUserTimezone(), true)
                    "
                    :total-minutes="
                        dailyTotals[
                            getDayJsInstance()(arg.date)
                                .utc()
                                .tz(getUserTimezone(), true)
                                .format('YYYY-MM-DD')
                        ] || 0
                    " />
            </template>
        </FullCalendar>
    </div>
</template>

<style scoped>
.fullcalendar {
    height: 100%;
    --fc-border-color: var(--border);
}

/* FullCalendar theme customization */
.fullcalendar :deep(.fc) {
    background-color: var(--theme-color-default-background);
    color: var(--foreground);
    font-family: inherit;
}

.fullcalendar :deep(.fc-timegrid-slot) {
    height: 25px;
    transition: height 0.2s ease;
}

.fullcalendar :deep(.fc-timegrid-slot-label) {
    background-color: var(--background);
}

.fullcalendar :deep(.fc-toolbar) {
    background-color: var(--background);
    padding: 0.5rem;
    margin-bottom: 0;
}

.fullcalendar :deep(.fc-toolbar-title) {
    color: var(--foreground);
    font-size: 1rem;
    font-weight: 600;
}

.fullcalendar :deep(.fc-button) {
    background-color: var(--secondary);
    border: 1px solid var(--border);
    color: var(--foreground);
    font-weight: 500;
    font-size: 14px !important;
}

.fullcalendar :deep(.fc-button:hover) {
    background-color: var(--muted);
    border-color: var(--border);
}

.fullcalendar :deep(.fc-button:focus) {
    box-shadow: 0 0 0 2px var(--ring);
}

.fullcalendar :deep(.fc-button-active) {
    background-color: var(--primary);
    border-color: var(--primary);
    color: var(--primary-foreground);
}

.fullcalendar :deep(.fc-col-header) {
    border-bottom: 1px solid var(--border);
}

.fullcalendar :deep(.fc-col-header-cell) {
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 0.75rem 0.5rem;
    background-color: var(--theme-color-default-background);
}

.fullcalendar :deep(.fc-timegrid-axis) {
    background-color: var(--theme-color-default-background) !important;
}

.fullcalendar :deep(.fc-col-header-cell .fc-col-header-cell-cushion) {
    padding: 0;
}

.fullcalendar :deep(.fc-timegrid-axis) {
    background-color: var(--theme-color-default-background);
    border-right: 1px solid var(--border);
}

/* Quarter-hour slots - transparent borders */
.fullcalendar :deep(.fc-timegrid-slot-minor.fc-timegrid-slot-label) {
    border-top: 1px solid transparent;
}

.fullcalendar :deep(.fc-timegrid-slot-minor.fc-timegrid-slot-lane) {
    --tw-border-opacity: 0;
}

.fullcalendar :deep(.fc-day-today.fc-col-header-cell) {
    background-color: var(--color-accent-default);
}

.fullcalendar :deep(.fc-day-today) {
    background-color: var(--theme-color-default-background);
}

.fullcalendar :deep(.fc-now-indicator) {
    border-color: var(--primary);
    border-width: 2px;
}

.fullcalendar :deep(.fc-event) {
    border-radius: calc(var(--radius) - 4px);
    padding: 0;
    font-size: 0.75rem;
    cursor: pointer;
    box-shadow: var(--theme-shadow-card);
    opacity: 0.9;
    overflow: hidden;
}

.fullcalendar :deep(.fc-v-event) {
    background-color: var(--muted);
    border-color: var(--muted);
}

.fullcalendar :deep(.fc-event-title) {
    font-weight: 500;
    line-height: 1.2;
}

/* Enhanced FullCalendar resize handles */
.fullcalendar :deep(.fc-event-resizer) {
    position: absolute;
    z-index: 99;
    background: '#FFF';
    border-radius: 2px;
    width: 100%;
    height: 4px;
    left: 0;
    transition: all 0.2s ease;
    opacity: 0;
}

.fullcalendar :deep(.fc-event-resizer-start) {
    top: -2px;
    cursor: n-resize;
}

.fullcalendar :deep(.fc-event-resizer-end) {
    bottom: -2px;
    cursor: s-resize;
}

.fullcalendar :deep(.fc-event:hover .fc-event-resizer) {
    opacity: 1;
}

.fullcalendar :deep(.fc-event-resizer:hover) {
    background: '#FFF';
    height: 6px;
}

/* Update the earlier hover rule to include the shadow */
.fullcalendar :deep(.fc-event:hover) {
    opacity: 1;
    transition: all 0.2s ease;
    box-shadow: var(--theme-shadow-dropdown);
}

.fullcalendar :deep(.fc-timegrid-event-harness) {
    margin: 0 1px;
}

.fullcalendar :deep(.fc-highlight) {
    background-color: var(--primary);
}

.fullcalendar :deep(.fc-select-mirror) {
    background-color: var(--accent);
    border: 1px solid var(--primary);
}

.fullcalendar :deep(.fc-scrollgrid) {
    border: 1px solid var(--border);
    border-left: 1px solid transparent;
}

.fullcalendar :deep(.fc-scrollgrid-section > td) {
    border-right: 1px solid var(--border);
}

.fullcalendar :deep(.fc-timegrid-body) {
    background-color: var(--background);
}

.fullcalendar :deep(.fc-timegrid-col) {
    border-right: 1px solid var(--border);
}

.fullcalendar :deep(.fc-timegrid-axis-cushion) {
    color: var(--theme-text-secondary);
    font-size: 0.75rem;
    font-weight: 500;
}

.fullcalendar :deep(.fc-timegrid-slot-label-cushion) {
    font-size: 0.8125rem;
    color: var(--muted-foreground);
}

.fullcalendar :deep(.fc-col-header-cell-cushion) {
    color: var(--foreground);
    font-size: 0.875rem;
    font-weight: 600;
}

/* Daily totals styling */
.fullcalendar :deep(.fc-col-header-cell .text-muted-foreground) {
    color: var(--muted-foreground);
    margin-top: 0.125rem;
}

/* Reduce visibility of time slot dividers */
.fullcalendar :deep(.fc-timegrid-divider) {
    display: none;
}

/* Make scrollbars gray */
.fullcalendar :deep(.fc-scroller) {
    scrollbar-width: thin;
    scrollbar-color: var(--muted-foreground) transparent;
}

.fullcalendar :deep(.fc-scroller::-webkit-scrollbar) {
    width: 8px;
}

.fullcalendar :deep(.fc-scroller::-webkit-scrollbar-track) {
    background: transparent;
}

.fullcalendar :deep(.fc-scroller::-webkit-scrollbar-thumb) {
    background-color: var(--muted-foreground);
    border-radius: 4px;
}

.fullcalendar :deep(.fc-scroller::-webkit-scrollbar-thumb:hover) {
    background-color: var(--foreground);
}

/* Improve time axis styling */
.fullcalendar :deep(.fc-timegrid-axis-chunk) {
    background-color: var(--theme-color-default-background);
}

/* Simple event main styling */
.fullcalendar :deep(.fc-event-main) {
    padding: 0.125rem 0.25rem;
}

/* Activity status plugin styles */
.fullcalendar :deep(.activity-status-box) {
    transition: opacity 0.2s ease;
}

.fullcalendar :deep(.activity-status-box.idle) {
    background-color: rgba(156, 163, 175, 0.1) !important;
}

.fullcalendar :deep(.activity-status-box.idle):hover {
    background-color: rgba(156, 163, 175, 0.5) !important;
}

.fullcalendar :deep(.activity-status-box.active) {
    background-color: rgba(34, 197, 94, 0.3) !important;
}

.fullcalendar :deep(.activity-status-box.active):hover {
    background-color: rgba(34, 197, 94, 1) !important;
}

/* Add left margin to events only on days with activity status data */
.fullcalendar :deep(.has-activity-status .fc-timegrid-event-harness) {
    margin-left: 15px !important;
}

.fullcalendar :deep(.fc-timegrid-event) {
    margin-left: 0 !important;
}
</style>
