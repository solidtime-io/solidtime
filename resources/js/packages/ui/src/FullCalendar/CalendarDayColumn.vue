<script setup lang="ts">
import FullCalendarEventContent from './FullCalendarEventContent.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '..';
import type { DayEvent, ActivityBox } from './calendarTypes';
import type { WindowActivityInPeriod } from './activityTypes';

const props = defineProps<{
    dayStr: string;
    totalGridHeight: number;
    hasActivityStatus: boolean;

    // Events
    dayEvents: DayEvent[];
    getEventStyle: (dayEvent: DayEvent, dayStr: string) => Record<string, string>;
    getEventOpacityClass: (dayEvent: DayEvent, dayStr: string) => string;
    getEventDurationSeconds: (dayEvent: DayEvent, dayStr: string) => number;

    // Drag state
    isDragging: boolean;
    dragEventId: string | null;
    dragPreview: Record<string, string> | undefined;

    // Resize state
    resizeEventId: string | null;
    resizeCrossDayPreview: Record<string, string> | undefined;

    // Now indicator
    showNowIndicator: boolean;
    nowIndicatorTop: number;

    // Activity boxes
    activityBoxes: ActivityBox[];
    getActivityBoxLabel: (box: ActivityBox) => string;
    getActivityBoxActivities: (box: ActivityBox) => WindowActivityInPeriod[];
    getActivityPercentage: (count: number, total: number) => string;
    getActivityText: (activity: WindowActivityInPeriod) => string;
    getTopActivity: (box: ActivityBox) => WindowActivityInPeriod | null;
    isDayView: boolean;

    // Selection
    showSelection: boolean;
    isSelectionStart: boolean;
    isSelectionIntermediate: boolean;
    isSelectionEnd: boolean;
    selectionTop: number;
    selectionHeight: number;
    selectionEndTop: number;
    selectionEndHeight: number;
}>();

function isUncoveredByEvents(abox: ActivityBox): boolean {
    return !props.dayEvents.some((de) => {
        const eTop = de.top;
        const eBottom = de.top + de.height;
        const aTop = abox.top;
        const aBottom = abox.top + abox.height;
        return eTop < aBottom && eBottom > aTop;
    });
}

const emit = defineEmits<{
    (e: 'event-pointerdown', event: PointerEvent, dayEvent: DayEvent): void;
    (e: 'event-keydown-enter', dayEvent: DayEvent): void;
    (
        e: 'resizer-pointerdown',
        event: PointerEvent,
        dayEvent: DayEvent,
        edge: 'start' | 'end'
    ): void;
    (e: 'activity-pointerdown', event: PointerEvent): void;
}>();
</script>

<template>
    <div
        class="fc-timegrid-col relative border-r border-border bg-transparent pointer-events-none"
        :class="{
            'has-activity-status': hasActivityStatus,
            'activity-expanded': hasActivityStatus && isDayView,
        }"
        :data-date="dayStr"
        :style="{ height: totalGridHeight + 'px' }">
        <div
            class="absolute inset-y-0 left-0.5 right-0.5"
            :class="{
                'fc-events-inset': hasActivityStatus && !isDayView,
                'fc-events-inset-expanded': hasActivityStatus && isDayView,
            }">
            <div
                v-for="dayEvent in dayEvents"
                :key="dayEvent.event.id"
                class="fc-event group pointer-events-auto rounded-sm text-xs cursor-pointer shadow-card border border-border touch-none select-none hover:shadow-dropdown focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1"
                :class="[
                    getEventOpacityClass(dayEvent, dayStr),
                    {
                        'running-entry rounded-b-none': dayEvent.event.isRunning,
                        'fc-event-dragging': isDragging && dragEventId === dayEvent.event.id,
                        'fc-event-resizing': resizeEventId === dayEvent.event.id,
                        'rounded-t-none': dayEvent.isClippedStart,
                        'rounded-b-none': dayEvent.isClippedEnd,
                        'fc-event-clipped-start': dayEvent.isClippedStart,
                        'fc-event-clipped-end': dayEvent.isClippedEnd,
                    },
                ]"
                :data-event-id="dayEvent.event.id"
                :style="getEventStyle(dayEvent, dayStr)"
                tabindex="0"
                :aria-label="dayEvent.event.title"
                role="button"
                @pointerdown="emit('event-pointerdown', $event, dayEvent)"
                @keydown.enter.prevent="emit('event-keydown-enter', dayEvent)">
                <div
                    v-if="!dayEvent.isClippedStart"
                    class="fc-event-resizer fc-event-resizer-start absolute z-[99] w-full h-3 left-0 top-[-2px] cursor-row-resize flex items-center justify-center opacity-0 group-hover:opacity-100"
                    @pointerdown.stop.prevent="
                        emit('resizer-pointerdown', $event, dayEvent, 'start')
                    "></div>
                <div class="px-1 py-0.5 h-full overflow-hidden">
                    <FullCalendarEventContent
                        :title="dayEvent.event.title"
                        :project-name="dayEvent.event.project?.name"
                        :task-name="dayEvent.event.task?.name"
                        :client-name="dayEvent.event.client?.name"
                        :duration-seconds="getEventDurationSeconds(dayEvent, dayStr)" />
                </div>
                <div
                    v-if="!dayEvent.event.isRunning && !dayEvent.isClippedEnd"
                    class="fc-event-resizer fc-event-resizer-end absolute z-[99] w-full h-3 left-0 bottom-[-2px] cursor-row-resize flex items-center justify-center opacity-0 group-hover:opacity-100"
                    @pointerdown.stop.prevent="
                        emit('resizer-pointerdown', $event, dayEvent, 'end')
                    "></div>
            </div>
        </div>

        <div
            v-if="showNowIndicator"
            class="fc-timegrid-now-indicator-line absolute left-0 right-0 border-t-2 border-red-500 z-50 pointer-events-none"
            :style="{ top: nowIndicatorTop + 'px' }"></div>

        <TooltipProvider :disable-hoverable-content="true" :delay-duration="0">
            <Tooltip v-for="(abox, ai) in activityBoxes" :key="'activity-' + ai">
                <TooltipTrigger as-child>
                    <div
                        class="activity-status-box"
                        :class="[
                            abox.isIdle ? 'idle' : 'active',
                            {
                                'activity-status-box-expanded': isDayView,
                                'activity-status-box-uncovered':
                                    !isDayView &&
                                    !abox.isIdle &&
                                    getTopActivity(abox) &&
                                    isUncoveredByEvents(abox),
                            },
                        ]"
                        :style="{ top: abox.top + 'px', height: abox.height + 'px' }"
                        @pointerdown="emit('activity-pointerdown', $event)">
                        <div
                            v-if="
                                !abox.isIdle &&
                                getTopActivity(abox) &&
                                abox.height >= 16 &&
                                (isDayView || isUncoveredByEvents(abox))
                            "
                            class="activity-status-content">
                            <img
                                v-if="getTopActivity(abox)?.icon"
                                :src="getTopActivity(abox)!.icon!"
                                :alt="getTopActivity(abox)!.appName"
                                class="activity-status-icon" />
                            <div v-else class="activity-status-icon-fallback">
                                {{ getTopActivity(abox)!.appName.charAt(0).toUpperCase() }}
                            </div>
                            <span class="activity-status-label">
                                {{ getTopActivity(abox)!.label || getTopActivity(abox)!.appName }}
                            </span>
                        </div>
                    </div>
                </TooltipTrigger>
                <TooltipContent :side="isDayView ? 'right' : 'left'" :side-offset="8">
                    <template v-if="getActivityBoxActivities(abox).length === 0">
                        {{ getActivityBoxLabel(abox) }}
                    </template>
                    <div v-else class="max-w-[300px]">
                        <div class="font-semibold mb-2">{{ getActivityBoxLabel(abox) }}</div>
                        <div
                            v-for="(activity, actIdx) in getActivityBoxActivities(abox).slice(0, 5)"
                            :key="actIdx"
                            class="mt-1 text-[11px] opacity-90 flex items-center gap-1.5">
                            <img
                                v-if="activity.icon"
                                :src="activity.icon"
                                :alt="activity.appName"
                                class="w-4 h-4 rounded-sm shrink-0" />
                            <div
                                v-else
                                class="w-4 h-4 rounded-sm bg-white/10 flex items-center justify-center text-[8px] shrink-0">
                                {{ activity.appName.charAt(0).toUpperCase() }}
                            </div>
                            <span class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap">
                                {{
                                    getActivityPercentage(
                                        activity.count,
                                        getActivityBoxActivities(abox).reduce(
                                            (sum, a) => sum + a.count,
                                            0
                                        )
                                    )
                                }}%
                                {{ getActivityText(activity) }}
                            </span>
                        </div>
                        <div
                            v-if="getActivityBoxActivities(abox).length > 5"
                            class="mt-1 text-[11px] opacity-70 italic">
                            ...and {{ getActivityBoxActivities(abox).length - 5 }} more
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>

        <div
            v-if="showSelection && isSelectionStart"
            class="absolute inset-x-0 pointer-events-none bg-accent border border-primary z-[2]"
            :style="{
                top: selectionTop + 'px',
                height: selectionHeight + 'px',
            }"></div>
        <div
            v-if="showSelection && isSelectionIntermediate"
            class="absolute inset-x-0 pointer-events-none bg-accent border border-primary z-[2]"
            :style="{
                top: '0px',
                height: totalGridHeight + 'px',
            }"></div>
        <div
            v-if="showSelection && isSelectionEnd"
            class="absolute inset-x-0 pointer-events-none bg-accent border border-primary z-[2]"
            :style="{
                top: selectionEndTop + 'px',
                height: selectionEndHeight + 'px',
            }"></div>

        <div
            v-if="isDragging && dragPreview"
            class="fc-cross-day-preview pointer-events-none mx-px"
            :style="dragPreview"></div>

        <div
            v-if="resizeCrossDayPreview"
            class="fc-cross-day-preview pointer-events-none mx-px"
            :style="resizeCrossDayPreview"></div>
    </div>
</template>

<style scoped>
.fc-event-resizer::after {
    content: '';
    width: 24px;
    height: 3px;
    border-radius: 1.5px;
    background: rgba(255, 255, 255, 0.6);
}
.fc-event-resizer:hover::after {
    background: rgba(255, 255, 255, 0.9);
}

.fc-event-resizing,
.fc-event-resizing .fc-event-resizer {
    cursor: row-resize !important;
}
.fc-event-resizing {
    box-shadow: var(--theme-shadow-dropdown);
}
.fc-event-resizing .fc-event-resizer {
    opacity: 1;
}
.fc-event-resizing .fc-event-resizer::after {
    background: rgba(255, 255, 255, 0.9);
}

.running-entry .fc-event-resizer-end {
    display: none;
}

.fc-timegrid-now-indicator-line::before {
    content: '';
    position: absolute;
    top: -5px;
    left: -4px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ef4444;
}

.activity-status-box {
    position: absolute;
    width: 10px;
    left: 0;
    z-index: 10;
    cursor: default;
    pointer-events: auto;
}
.activity-status-box::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 5px;
    transition: opacity 0.2s ease;
}
.activity-status-box.idle::before {
    background-color: rgba(156, 163, 175, 0.1);
}
.activity-status-box.idle:hover::before {
    background-color: rgba(156, 163, 175, 0.5);
}
.activity-status-box.active::before {
    background-color: rgba(14, 165, 233, 0.3);
}
.activity-status-box.active:hover::before {
    background-color: rgba(14, 165, 233, 1);
}

/* Uncovered activity boxes in week view — fill column width */
.activity-status-box-uncovered {
    width: calc(100% - 4px);
    border-radius: 3px;
    overflow: hidden;
}
.activity-status-box-uncovered::before {
    left: 0;
    right: 0;
    width: auto;
}
.activity-status-box-uncovered.active::before {
    background-color: rgba(14, 165, 233, 0.12);
}
.activity-status-box-uncovered.active:hover::before {
    background-color: rgba(14, 165, 233, 0.25);
}

/* Expanded activity boxes for day view */
.activity-status-box-expanded {
    width: 200px;
    border-radius: 3px;
    overflow: hidden;
}
.activity-status-box-expanded::before {
    left: 0;
    right: 0;
    width: auto;
}
.activity-status-box-expanded.idle::before {
    background-color: rgba(156, 163, 175, 0.08);
}
.activity-status-box-expanded.idle:hover::before {
    background-color: rgba(156, 163, 175, 0.2);
}
.activity-status-box-expanded.active::before {
    background-color: rgba(14, 165, 233, 0.12);
}
.activity-status-box-expanded.active:hover::before {
    background-color: rgba(14, 165, 233, 0.25);
}

.activity-status-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 4px;
    height: 100%;
    overflow: hidden;
}

.activity-status-icon {
    width: 14px;
    height: 14px;
    border-radius: 2px;
    flex-shrink: 0;
}

.activity-status-icon-fallback {
    width: 14px;
    height: 14px;
    border-radius: 2px;
    background-color: rgba(14, 165, 233, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    flex-shrink: 0;
    color: rgba(14, 165, 233, 0.8);
}

.activity-status-label {
    font-size: 10px;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: 0.8;
}

.fc-events-inset {
    left: 8px;
}

.fc-events-inset-expanded {
    left: 204px;
}
</style>
