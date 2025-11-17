import { createPlugin, PluginDef } from '@fullcalendar/core';

export interface ActivityPeriod {
    start: string;
    end: string;
    isIdle: boolean;
}

export interface ActivityStatusPluginOptions {
    activityPeriods?: ActivityPeriod[];
}

// Extend FullCalendar's options interface
declare module '@fullcalendar/core' {
    interface CalendarOptions {
        activityPeriods?: ActivityPeriod[];
    }
}

/**
 * Renders activity status boxes in the calendar time grid
 */
export function renderActivityStatusBoxes(
    calendarEl: HTMLElement,
    activityPeriods: ActivityPeriod[]
) {
    if (!calendarEl) return;

    // Clean up existing activity boxes and markers first
    const existingBoxes = calendarEl.querySelectorAll('.activity-status-box');
    existingBoxes.forEach((box) => box.remove());

    // Remove has-activity-status class from all lanes
    const allLanes = calendarEl.querySelectorAll('.fc-timegrid-col');
    allLanes.forEach((lane) => lane.classList.remove('has-activity-status'));

    const timeGrid = calendarEl.querySelector('.fc-timegrid-body');
    if (!timeGrid) {
        console.log('No timegrid found');
        return;
    }

    const lanes = timeGrid.querySelectorAll('.fc-timegrid-col');
    if (lanes.length === 0) {
        console.log('No lanes found');
        return;
    }

    console.log(
        'Rendering activity status boxes, lanes:',
        lanes.length,
        'periods:',
        activityPeriods.length
    );

    // Get the calendar's current view to determine dates
    const dateHeaders = calendarEl.querySelectorAll('.fc-col-header-cell');

    lanes.forEach((lane: Element, dayIndex: number) => {
        // Get the date for this lane from the data attribute
        const laneEl = lane as HTMLElement;
        const dateStr = laneEl.getAttribute('data-date');

        if (!dateStr) {
            console.log('No date attribute found for lane', dayIndex);
            return;
        }

        const laneDate = new Date(dateStr);
        const laneDateStart = new Date(laneDate);
        laneDateStart.setHours(0, 0, 0, 0);
        const laneDateEnd = new Date(laneDate);
        laneDateEnd.setHours(23, 59, 59, 999);

        console.log('Processing lane', dayIndex, 'date:', dateStr);

        let hasActivityStatusForThisDay = false;

        activityPeriods.forEach((period) => {
            const periodStart = new Date(period.start);
            const periodEnd = new Date(period.end);

            // Check if period overlaps with this day
            if (periodEnd < laneDateStart || periodStart > laneDateEnd) {
                return;
            }

            console.log(
                'Rendering period',
                period.isIdle ? 'idle' : 'active',
                'from',
                periodStart,
                'to',
                periodEnd
            );

            // Calculate the position and height of the idle box
            const { top, height } = calculateBoxPosition(
                calendarEl,
                periodStart > laneDateStart ? periodStart : laneDateStart,
                periodEnd < laneDateEnd ? periodEnd : laneDateEnd
            );

            if (height <= 0) return;

            hasActivityStatusForThisDay = true;

            // Create and append the activity status box
            const box = document.createElement('div');
            box.className = `activity-status-box ${period.isIdle ? 'idle' : 'active'}`;
            box.style.position = 'absolute';
            box.style.top = `${top}px`;
            box.style.height = `${height}px`;
            box.style.width = '8px';
            box.style.left = '4px';
            box.style.right = '4px';
            box.style.zIndex = '10';
            box.style.borderRadius = '4px';

            // Position relative to the lane
            const laneFrame = lane.querySelector('.fc-timegrid-col-frame');
            if (laneFrame) {
                laneFrame.appendChild(box);
            } else {
                console.log('No lane frame found');
            }
        });

        // Mark this lane as having activity status if any periods were rendered
        if (hasActivityStatusForThisDay) {
            laneEl.classList.add('has-activity-status');
        }
    });
}

/**
 * Calculates the pixel position and height for an activity status box
 */
function calculateBoxPosition(
    calendarEl: HTMLElement,
    startTime: Date,
    endTime: Date
): { top: number; height: number } {
    // Get the slot duration and slot height
    const slotsEl = calendarEl.querySelectorAll('.fc-timegrid-slot');
    if (slotsEl.length === 0) {
        console.log('No slots found');
        return { top: 0, height: 0 };
    }

    // Calculate slot height (assuming all slots are equal height)
    const firstSlot = slotsEl[0] as HTMLElement;
    const slotHeight = firstSlot.offsetHeight;

    // Each slot is 15 minutes by default (configured in TimeEntryCalendar)
    const slotDurationMinutes = 15;
    const pixelsPerMinute = slotHeight / slotDurationMinutes;

    // Calculate start position (minutes from midnight)
    const startMinutes = startTime.getHours() * 60 + startTime.getMinutes();
    const endMinutes = endTime.getHours() * 60 + endTime.getMinutes();

    // Calculate pixel positions
    const top = startMinutes * pixelsPerMinute;
    const height = (endMinutes - startMinutes) * pixelsPerMinute;

    return { top, height };
}

/**
 * FullCalendar plugin to display idle/active status boxes in the time grid
 */
const activityStatusPlugin: PluginDef = createPlugin({
    name: '@solidtime/activity-status',

    optionRefiners: {
        activityPeriods: (rawVal: any) => {
            if (!Array.isArray(rawVal)) return [];
            return rawVal;
        },
    },
});

export default activityStatusPlugin;
