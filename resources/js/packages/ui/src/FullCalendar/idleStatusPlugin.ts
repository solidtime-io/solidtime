import { createPlugin, type PluginDef } from '@fullcalendar/core';
import { computePosition, flip, shift, offset } from '@floating-ui/dom';

export interface ActivityPeriod {
    start: string;
    end: string;
    isIdle: boolean;
}

export interface ActivityStatusPluginOptions {
    activityPeriods?: ActivityPeriod[];
}

/**
 * Creates and manages a tooltip element for activity status boxes
 */
function createTooltip(): HTMLElement {
    const tooltip = document.createElement('div');
    tooltip.className =
        'z-50 overflow-hidden rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground';
    tooltip.style.position = 'fixed';
    tooltip.style.pointerEvents = 'none';
    tooltip.style.opacity = '0';
    tooltip.style.whiteSpace = 'nowrap';
    tooltip.style.transform = 'scale(0.95)';
    tooltip.style.transition = 'opacity 150ms, transform 150ms';
    document.body.appendChild(tooltip);
    return tooltip;
}

/**
 * Shows tooltip for an activity status box
 */
function showTooltip(box: HTMLElement, tooltip: HTMLElement, text: string) {
    tooltip.textContent = text;
    tooltip.style.opacity = '1';
    tooltip.style.transform = 'scale(1)';

    const updatePosition = () => {
        computePosition(box, tooltip, {
            placement: 'right',
            middleware: [offset(8), flip(), shift({ padding: 5 })],
        }).then(({ x, y }) => {
            tooltip.style.left = `${x}px`;
            tooltip.style.top = `${y}px`;
        });
    };

    updatePosition();
}

/**
 * Hides the tooltip
 */
function hideTooltip(tooltip: HTMLElement) {
    tooltip.style.opacity = '0';
    tooltip.style.transform = 'scale(0.95)';
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

    // Clean up existing tooltips
    const existingTooltips = document.querySelectorAll('.activity-status-tooltip');
    existingTooltips.forEach((tooltip) => tooltip.remove());

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

    // Create a single tooltip instance to be reused
    const tooltip = createTooltip();

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

        let hasActivityStatusForThisDay = false;

        activityPeriods.forEach((period) => {
            const periodStart = new Date(period.start);
            const periodEnd = new Date(period.end);

            // Check if period overlaps with this day
            if (periodEnd < laneDateStart || periodStart > laneDateEnd) {
                return;
            }

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
            box.style.cursor = 'default';

            // Calculate duration in minutes
            const actualStart = periodStart > laneDateStart ? periodStart : laneDateStart;
            const actualEnd = periodEnd < laneDateEnd ? periodEnd : laneDateEnd;
            const durationMs = actualEnd.getTime() - actualStart.getTime();
            const durationMinutes = Math.round(durationMs / 60000);

            // Format duration
            const hours = Math.floor(durationMinutes / 60);
            const minutes = durationMinutes % 60;
            const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

            // Add tooltip text based on status
            const status = period.isIdle ? 'Idling' : 'Active';
            const tooltipText = `${status} (${durationText})`;

            // Add hover event listeners for tooltip
            box.addEventListener('mouseenter', () => {
                showTooltip(box, tooltip, tooltipText);
            });

            box.addEventListener('mouseleave', () => {
                hideTooltip(tooltip);
            });

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
        activityPeriods: (rawVal: unknown): ActivityPeriod[] => {
            if (!Array.isArray(rawVal)) return [];
            return rawVal as ActivityPeriod[];
        },
    },
});

export default activityStatusPlugin;
