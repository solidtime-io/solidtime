<script setup lang="ts">
import {
    ref,
    watch,
    inject,
    type ComputedRef,
    nextTick,
    onMounted,
    onActivated,
    onDeactivated,
    onUnmounted,
} from 'vue';
import { useLocalStorage } from '@vueuse/core';
import { useCssVariable } from '../utils/useCssVariable';
import { getLocalizedDayJs } from '../utils/time';
import { LoadingSpinner, TimeEntryCreateModal, TimeEntryEditModal } from '..';
import FullCalendarDayHeader from './FullCalendarDayHeader.vue';
import CalendarToolbar from './CalendarToolbar.vue';
import CalendarDayColumn from './CalendarDayColumn.vue';
import type { CalendarSettings } from './calendarSettings';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '..';
import {
    PencilIcon,
    DocumentDuplicateIcon,
    TrashIcon,
    ScissorsIcon,
    PlusIcon,
    StopIcon,
    XMarkIcon,
} from '@heroicons/vue/20/solid';
import type { ActivityPeriod } from './activityTypes';
import { SLOT_HEIGHT, TIME_AXIS_WIDTH, type DayEvent } from './calendarTypes';
import { useCalendarGrid } from './useCalendarGrid';
import { useCalendarNavigation } from './useCalendarNavigation';
import { useCalendarEvents } from './useCalendarEvents';
import { useActivityBoxes } from './useActivityBoxes';
import { useEventDrag } from './useEventDrag';
import { useEventResize } from './useEventResize';
import { useSlotSelection } from './useSlotSelection';
import { useContextMenu } from './useContextMenu';
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

    enableEstimatedTime: boolean;
    currency: string;
    canCreateProject: boolean;
    organizationBillableRate: number | null;

    createTimeEntry: (
        entry: Omit<TimeEntry, 'id' | 'organization_id' | 'user_id'>
    ) => Promise<void>;
    updateTimeEntry: (entry: TimeEntry) => Promise<void>;
    deleteTimeEntry: (timeEntryId: string) => Promise<void>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
}>();

const newEventStart = ref<Dayjs | null>(null);
const newEventEnd = ref<Dayjs | null>(null);
const showCreateTimeEntryModal = ref<boolean>(false);
const showEditTimeEntryModal = ref<boolean>(false);
const selectedTimeEntry = ref<TimeEntry | null>(null);
const contextMenuOpen = ref(false);

const rootRef = ref<HTMLElement | null>(null);
const scrollerRef = ref<HTMLElement | null>(null);

const calendarSettings = useLocalStorage<CalendarSettings>(
    'solidtime:calendar-settings',
    {
        snapMinutes: 15,
        startHour: 0,
        endHour: 24,
        slotMinutes: 15,
    },
    { mergeDefaults: true }
);

function onSettingsUpdate(newSettings: CalendarSettings) {
    calendarSettings.value = newSettings;
}

const currentTime = ref(getLocalizedDayJs());
let currentTimeInterval: ReturnType<typeof setInterval> | null = null;

const organization = inject<ComputedRef<Organization>>('organization');

const {
    slots,
    totalGridHeight,
    formatSlotLabel,
    minutesToPixels,
    pixelsToMinutesFromMidnight,
    timeToMinutesFromMidnight,
    getDayFromClientX,
    clientYToGridPixels,
} = useCalendarGrid(calendarSettings, organization, scrollerRef, rootRef);

const {
    activeView,
    viewDays,
    viewTitle,
    emitDatesChange,
    handlePrev,
    handleNext,
    handleToday,
    handleChangeView,
} = useCalendarNavigation({
    onDatesChange: (payload) => emit('dates-change', payload),
    scrollToCurrentTime: () => scrollToCurrentTime(),
});

const cssBackground = useCssVariable('--color-bg-background');

const { optimisticOverrides, calendarEvents, eventsByDay, dailyTotals, isToday, nowIndicatorTop } =
    useCalendarEvents({
        timeEntries: () => props.timeEntries,
        projects: () => props.projects,
        clients: () => props.clients,
        tasks: () => props.tasks,
        calendarSettings,
        viewDays,
        currentTime,
        cssBackground,
        minutesToPixels,
        timeToMinutesFromMidnight,
    });

const {
    activityBoxesForDay,
    dayHasActivityStatus,
    getActivityBoxLabel,
    getActivityBoxActivities,
    getActivityPercentage,
    getActivityText,
    getTopActivity,
} = useActivityBoxes({
    activityPeriods: () => props.activityPeriods,
    viewDays,
    calendarSettings,
    minutesToPixels,
});

const { isDragging, dragEventId, dragPreviewsByDay, onEventPointerDown } = useEventDrag({
    calendarSettings,
    viewDays,
    optimisticOverrides,
    updateTimeEntry: (entry) => props.updateTimeEntry(entry),
    emitRefresh: () => emit('refresh'),
    minutesToPixels,
    pixelsToMinutesFromMidnight,
    getDayFromClientX,
    clientYToGridPixels,
    onClickEvent: (ev) => {
        selectedTimeEntry.value = ev.timeEntry;
        showEditTimeEntryModal.value = true;
    },
});

const {
    isResizing,
    resizeEventId,
    resizeCurrentTop,
    resizeCurrentHeight,
    resizeCrossDayPreviewsByDay,
    resizeLiveDurationSeconds,
    getResizeOriginalDayStr,
    onResizerPointerDown,
} = useEventResize({
    calendarSettings,
    viewDays,
    eventsByDay,
    optimisticOverrides,
    updateTimeEntry: (entry) => props.updateTimeEntry(entry),
    emitRefresh: () => emit('refresh'),
    minutesToPixels,
    pixelsToMinutesFromMidnight,
    getDayFromClientX,
    clientYToGridPixels,
});

const {
    isSelecting,
    selectionDay,
    selectionTop,
    selectionHeight,
    selectionEndDay,
    selectionEndTop,
    selectionEndHeight,
    selectionIntermediateDays,
    onSlotPointerDown,
    clearSelection,
} = useSlotSelection({
    calendarSettings,
    viewDays,
    totalGridHeight,
    pixelsToMinutesFromMidnight,
    getDayFromClientX,
    clientYToGridPixels,
    onSelectionComplete: (start, end) => {
        newEventStart.value = start;
        newEventEnd.value = end;
        showCreateTimeEntryModal.value = true;
    },
});

const {
    contextMenuTimeEntry,
    handleCalendarContextMenu,
    handleContextEdit,
    handleContextDuplicate,
    handleContextDelete,
    handleContextSplit,
    handleContextStop,
    handleContextDiscard,
    handleContextCreate,
} = useContextMenu({
    calendarSettings,
    calendarEvents,
    pixelsToMinutesFromMidnight,
    getDayFromClientX,
    clientYToGridPixels,
    createTimeEntry: (entry) => props.createTimeEntry(entry),
    updateTimeEntry: (entry) => props.updateTimeEntry(entry),
    deleteTimeEntry: (id) => props.deleteTimeEntry(id),
    onEditEvent: (entry) => {
        selectedTimeEntry.value = entry;
        showEditTimeEntryModal.value = true;
    },
    onCreateEvent: (start, end) => {
        newEventStart.value = start;
        newEventEnd.value = end;
        showCreateTimeEntryModal.value = true;
    },
    emitRefresh: () => emit('refresh'),
});

watch(showCreateTimeEntryModal, (value) => {
    if (!value) {
        newEventStart.value = null;
        newEventEnd.value = null;
        clearSelection();
        emit('refresh');
    }
});

watch(showEditTimeEntryModal, (value) => {
    if (!value) {
        selectedTimeEntry.value = null;
        emit('refresh');
    }
});

/**
 * Guards slot pointer-down so that clicks which dismiss an open Reka UI
 * layer (context menu, popover, dialog) don't simultaneously start a
 * new time-entry selection on the calendar grid.
 *
 * Because Reka's DismissableLayer registers its document-level
 * `pointerdown` listener *without* capture, it fires AFTER the
 * calendar grid's own handler. That means when this guard runs,
 * `contextMenuOpen` (and modal refs) still reflect the *open* state.
 */
function guardedSlotPointerDown(e: PointerEvent) {
    if (contextMenuOpen.value) return;
    if (showCreateTimeEntryModal.value || showEditTimeEntryModal.value) return;
    onSlotPointerDown(e);
}

const scrollToCurrentTime = () => {
    nextTick(() => {
        if (!scrollerRef.value) return;
        const now = getLocalizedDayJs();
        const oneHourBefore = now.subtract(1, 'hour');
        const s = calendarSettings.value;
        const startMin = s.startHour * 60;

        const targetMinutes = now.isSame(oneHourBefore, 'day')
            ? oneHourBefore.hour() * 60 + oneHourBefore.minute()
            : now.hour() * 60 + now.minute();

        const scrollTop = minutesToPixels(Math.max(0, targetMinutes - startMin));
        scrollerRef.value.scrollTop = scrollTop;
    });
};

watch(
    () => props.timeEntries,
    () => {
        if (optimisticOverrides.value.size > 0) {
            optimisticOverrides.value = new Map();
        }
    }
);

watch(
    calendarSettings,
    () => {
        emitDatesChange();
    },
    { deep: true }
);

let hasScrolledOnLoad = false;

watch(
    () => props.loading,
    (loading) => {
        if (!loading && !hasScrolledOnLoad) {
            hasScrolledOnLoad = true;
            scrollToCurrentTime();
        }
    }
);

onMounted(() => {
    scrollToCurrentTime();
    emitDatesChange();
    currentTimeInterval = setInterval(() => {
        currentTime.value = getLocalizedDayJs();
    }, 60000);
});

onActivated(() => {
    scrollToCurrentTime();
});

onDeactivated(() => {
    contextMenuOpen.value = false;
});

onUnmounted(() => {
    if (currentTimeInterval) {
        clearInterval(currentTimeInterval);
        currentTimeInterval = null;
    }
});

function getEventStyle(dayEvent: DayEvent, dayStr: string): Record<string, string> {
    const ev = dayEvent.event;
    const isResizeTarget = resizeEventId.value === ev.id;

    let top = dayEvent.top;
    let height = dayEvent.height;
    const left = dayEvent.left;
    const width = dayEvent.width;
    let zIndex = '1';

    if (isResizeTarget) {
        const isOnResizeOriginDay = dayStr === getResizeOriginalDayStr();
        if (isOnResizeOriginDay) {
            top = resizeCurrentTop.value;
            height = resizeCurrentHeight.value;
            zIndex = '100';
        }
    }

    return {
        position: 'absolute',
        top: `${top}px`,
        height: `${height}px`,
        left,
        width,
        backgroundColor: ev.backgroundColor,
        borderColor: ev.borderColor,
        zIndex,
    };
}

function getEventOpacityClass(dayEvent: DayEvent, dayStr: string): string {
    const ev = dayEvent.event;
    const isDragTarget = isDragging.value && dragEventId.value === ev.id;
    const isResizeTarget = resizeEventId.value === ev.id;

    if (isDragTarget) return 'opacity-30';

    if (isResizeTarget) {
        const isOnResizeOriginDay = dayStr === getResizeOriginalDayStr();
        if (!isOnResizeOriginDay) return 'opacity-50';
        return 'opacity-100';
    }

    return 'opacity-90 hover:opacity-100';
}

function getEventDurationSeconds(dayEvent: DayEvent, dayStr: string): number {
    const ev = dayEvent.event;
    const isResizeTarget = resizeEventId.value === ev.id;

    if (
        isResizeTarget &&
        dayStr === getResizeOriginalDayStr() &&
        resizeLiveDurationSeconds.value !== null
    ) {
        return resizeLiveDurationSeconds.value;
    }

    return ev.durationMinutes * 60;
}
</script>

<template>
    <div class="w-full relative h-full flex-1 flex flex-col overflow-hidden min-h-0">
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
            :organization-billable-rate="organizationBillableRate"
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
            :can-create-project="canCreateProject"
            :organization-billable-rate="organizationBillableRate" />

        <template v-if="!loading">
            <CalendarToolbar
                :view-title="viewTitle"
                :active-view="activeView"
                :settings="calendarSettings"
                @prev="handlePrev"
                @next="handleNext"
                @today="handleToday"
                @change-view="handleChangeView"
                @update:settings="onSettingsUpdate" />

            <ContextMenu v-model:open="contextMenuOpen">
                <ContextMenuTrigger
                    as="div"
                    class="flex-1 min-h-0"
                    @contextmenu="handleCalendarContextMenu">
                    <div
                        ref="rootRef"
                        class="fc h-full flex flex-col bg-default-background text-foreground font-inherit border border-border border-l-transparent overflow-hidden">
                        <div
                            class="fc-header-scroll flex border-b border-border shrink-0 sticky top-0 z-10 bg-default-background">
                            <div
                                class="shrink-0 bg-background border-r border-border"
                                :style="{
                                    width: TIME_AXIS_WIDTH + 'px',
                                    minWidth: TIME_AXIS_WIDTH + 'px',
                                }"></div>
                            <div
                                class="grid flex-1 min-w-0"
                                :style="{
                                    gridTemplateColumns: 'repeat(' + viewDays.length + ', 1fr)',
                                }">
                                <div
                                    v-for="day in viewDays"
                                    :key="day.format('YYYY-MM-DD')"
                                    class="fc-col-header-cell border-r border-border px-2 py-3 bg-default-background text-center"
                                    :class="{
                                        'bg-secondary': isToday(day),
                                        'fc-day-today': isToday(day),
                                    }"
                                    :data-date="day.format('YYYY-MM-DD')">
                                    <FullCalendarDayHeader
                                        :date="day"
                                        :is-today="isToday(day)"
                                        :total-seconds="
                                            dailyTotals[day.format('YYYY-MM-DD')] || 0
                                        " />
                                </div>
                            </div>
                        </div>

                        <div ref="scrollerRef" class="fc-scroller">
                            <div class="flex min-w-0">
                                <div
                                    class="shrink-0 bg-background border-r border-border"
                                    :style="{
                                        width: TIME_AXIS_WIDTH + 'px',
                                        minWidth: TIME_AXIS_WIDTH + 'px',
                                    }">
                                    <div
                                        v-for="slot in slots"
                                        :key="slot.time"
                                        class="fc-timegrid-slot fc-timegrid-slot-label relative text-right border-t border-border pr-1.5 pt-2 box-border"
                                        :class="{
                                            'fc-timegrid-slot-minor border-t-transparent':
                                                !slot.isHour,
                                        }"
                                        :data-time="slot.time"
                                        :style="{ height: SLOT_HEIGHT + 'px' }">
                                        <span
                                            v-if="slot.isHour"
                                            class="fc-timegrid-slot-label-cushion text-[0.8125rem] text-muted-foreground leading-none block font-light">
                                            {{ formatSlotLabel(slot.minutes / 60) }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="flex-1 min-w-0 relative"
                                    @pointerdown="guardedSlotPointerDown($event)">
                                    <div
                                        class="bg-background relative"
                                        :style="{ height: totalGridHeight + 'px' }">
                                        <div
                                            class="absolute inset-0 grid"
                                            :style="{
                                                gridTemplateColumns:
                                                    'repeat(' + viewDays.length + ', 1fr)',
                                            }">
                                            <div
                                                v-for="day in viewDays"
                                                :key="'bg-' + day.format('YYYY-MM-DD')"
                                                :style="
                                                    isToday(day)
                                                        ? {
                                                              backgroundColor:
                                                                  'var(--theme-color-default-background)',
                                                          }
                                                        : undefined
                                                " />
                                        </div>
                                        <div
                                            v-for="slot in slots"
                                            :key="'lane-' + slot.time"
                                            class="fc-timegrid-slot fc-timegrid-slot-lane border-t border-border box-border relative"
                                            :class="{
                                                'fc-timegrid-slot-minor border-dotted':
                                                    !slot.isHour,
                                            }"
                                            :data-time="slot.time"
                                            :style="{ height: SLOT_HEIGHT + 'px' }"></div>
                                    </div>
                                    <div
                                        class="grid absolute inset-0 pointer-events-none min-w-0"
                                        :style="{
                                            gridTemplateColumns:
                                                'repeat(' + viewDays.length + ', 1fr)',
                                        }">
                                        <CalendarDayColumn
                                            v-for="day in viewDays"
                                            :key="day.format('YYYY-MM-DD')"
                                            :day-str="day.format('YYYY-MM-DD')"
                                            :total-grid-height="totalGridHeight"
                                            :has-activity-status="
                                                dayHasActivityStatus(day.format('YYYY-MM-DD'))
                                            "
                                            :day-events="
                                                eventsByDay[day.format('YYYY-MM-DD')] || []
                                            "
                                            :get-event-style="getEventStyle"
                                            :get-event-opacity-class="getEventOpacityClass"
                                            :get-event-duration-seconds="getEventDurationSeconds"
                                            :is-dragging="isDragging"
                                            :drag-event-id="dragEventId"
                                            :drag-preview="
                                                dragPreviewsByDay[day.format('YYYY-MM-DD')]
                                            "
                                            :resize-event-id="resizeEventId"
                                            :resize-cross-day-preview="
                                                isResizing
                                                    ? resizeCrossDayPreviewsByDay[
                                                          day.format('YYYY-MM-DD')
                                                      ]
                                                    : undefined
                                            "
                                            :show-now-indicator="
                                                isToday(day) && nowIndicatorTop >= 0
                                            "
                                            :now-indicator-top="nowIndicatorTop"
                                            :activity-boxes="
                                                activityBoxesForDay(day.format('YYYY-MM-DD'))
                                            "
                                            :get-activity-box-label="getActivityBoxLabel"
                                            :get-activity-box-activities="getActivityBoxActivities"
                                            :get-activity-percentage="getActivityPercentage"
                                            :get-activity-text="getActivityText"
                                            :get-top-activity="getTopActivity"
                                            :is-day-view="activeView === 'timeGridDay'"
                                            :show-selection="
                                                isSelecting || showCreateTimeEntryModal
                                            "
                                            :is-selection-start="
                                                selectionDay === day.format('YYYY-MM-DD')
                                            "
                                            :is-selection-intermediate="
                                                selectionIntermediateDays.has(
                                                    day.format('YYYY-MM-DD')
                                                )
                                            "
                                            :is-selection-end="
                                                selectionEndDay === day.format('YYYY-MM-DD')
                                            "
                                            :selection-top="selectionTop"
                                            :selection-height="selectionHeight"
                                            :selection-end-top="selectionEndTop"
                                            :selection-end-height="selectionEndHeight"
                                            @activity-pointerdown="guardedSlotPointerDown"
                                            @event-pointerdown="
                                                (e, dayEvent) =>
                                                    onEventPointerDown(e, dayEvent.event, dayEvent)
                                            "
                                            @event-keydown-enter="
                                                (dayEvent) => {
                                                    selectedTimeEntry = dayEvent.event.timeEntry;
                                                    showEditTimeEntryModal = true;
                                                }
                                            "
                                            @resizer-pointerdown="
                                                (e, dayEvent, edge) =>
                                                    onResizerPointerDown(
                                                        e,
                                                        dayEvent.event,
                                                        dayEvent,
                                                        edge,
                                                        day.format('YYYY-MM-DD')
                                                    )
                                            " />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ContextMenuTrigger>

                <ContextMenuContent class="min-w-[160px]">
                    <template v-if="contextMenuTimeEntry && contextMenuTimeEntry.end !== null">
                        <ContextMenuItem class="space-x-3" @select="handleContextEdit()">
                            <PencilIcon class="w-4 h-4 text-icon-default" />
                            <span>Edit</span>
                        </ContextMenuItem>
                        <ContextMenuItem class="space-x-3" @select="handleContextDuplicate()">
                            <DocumentDuplicateIcon class="w-4 h-4 text-icon-default" />
                            <span>Duplicate</span>
                        </ContextMenuItem>
                        <ContextMenuItem class="space-x-3" @select="handleContextSplit()">
                            <ScissorsIcon class="w-4 h-4 text-icon-default" />
                            <span>Split</span>
                        </ContextMenuItem>
                        <ContextMenuSeparator />
                        <ContextMenuItem
                            class="space-x-3 text-destructive"
                            @select="handleContextDelete()">
                            <TrashIcon class="w-4 h-4 text-icon-default" />
                            <span>Delete</span>
                        </ContextMenuItem>
                    </template>
                    <template v-else-if="contextMenuTimeEntry && contextMenuTimeEntry.end === null">
                        <ContextMenuItem class="space-x-3" @select="handleContextStop()">
                            <StopIcon class="w-4 h-4 text-icon-default" />
                            <span>Stop</span>
                        </ContextMenuItem>
                        <ContextMenuSeparator />
                        <ContextMenuItem
                            class="space-x-3 text-destructive"
                            @select="handleContextDiscard()">
                            <XMarkIcon class="w-4 h-4 text-icon-default" />
                            <span>Discard</span>
                        </ContextMenuItem>
                    </template>
                    <template v-else>
                        <ContextMenuItem class="space-x-3" @select="handleContextCreate()">
                            <PlusIcon class="w-4 h-4 text-icon-default" />
                            <span>Create Time Entry</span>
                        </ContextMenuItem>
                    </template>
                </ContextMenuContent>
            </ContextMenu>
        </template>
    </div>
</template>

<style scoped>
.fc-header-scroll {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-gutter: stable;
    scrollbar-color: transparent transparent;
}
.fc-header-scroll::-webkit-scrollbar {
    width: 8px;
}
.fc-header-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.fc-header-scroll::-webkit-scrollbar-thumb {
    background-color: transparent;
}

.fc-scroller {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
    scrollbar-width: thin;
    scrollbar-color: var(--muted-foreground) transparent;
    scrollbar-gutter: stable;
}
.fc-scroller::-webkit-scrollbar {
    width: 8px;
}
.fc-scroller::-webkit-scrollbar-track {
    background: transparent;
}
.fc-scroller::-webkit-scrollbar-thumb {
    background-color: var(--muted-foreground);
    border-radius: 4px;
}
.fc-scroller::-webkit-scrollbar-thumb:hover {
    background-color: var(--foreground);
}
</style>

<style>
body.fc-resizing-active,
body.fc-resizing-active * {
    cursor: row-resize !important;
}
</style>
