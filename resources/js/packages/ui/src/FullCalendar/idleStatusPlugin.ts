import { createPlugin, type PluginDef } from '@fullcalendar/core';
import { computePosition, flip, shift, offset, autoUpdate } from '@floating-ui/dom';

export interface WindowActivityInPeriod {
    appName: string;
    url: string | null;
    count: number;
    icon?: string | null;
}

export interface ActivityPeriod {
    start: string;
    end: string;
    isIdle: boolean;
    windowActivities?: WindowActivityInPeriod[];
}

export interface ActivityStatusPluginOptions {
    activityPeriods?: ActivityPeriod[];
}

// Tooltip state management - single instance per module
let tooltipInstance: HTMLElement | null = null;
let cleanupAutoUpdate: (() => void) | null = null;

/**
 * Creates and manages a tooltip element for activity status boxes
 */
function getOrCreateTooltip(): HTMLElement {
    if (!tooltipInstance) {
        tooltipInstance = document.createElement('div');
        tooltipInstance.className =
            'z-50 overflow-hidden rounded-md bg-primary px-3 py-1.5 text-xs text-primary-foreground';
        tooltipInstance.style.position = 'fixed';
        tooltipInstance.style.pointerEvents = 'none';
        tooltipInstance.style.opacity = '0';
        tooltipInstance.style.whiteSpace = 'nowrap';
        tooltipInstance.style.transform = 'scale(0.95)';
        tooltipInstance.style.transition = 'opacity 150ms, transform 150ms';
        document.body.appendChild(tooltipInstance);
    }
    return tooltipInstance;
}

/**
 * Shows tooltip for an activity status box using Floating UI's autoUpdate
 */
function showTooltip(box: HTMLElement, tooltip: HTMLElement, content: string | HTMLElement) {
    // Clear previous content
    tooltip.innerHTML = '';

    if (typeof content === 'string') {
        tooltip.textContent = content;
    } else {
        tooltip.appendChild(content);
    }

    tooltip.style.opacity = '1';
    tooltip.style.transform = 'scale(1)';

    // Clean up previous autoUpdate if it exists
    if (cleanupAutoUpdate) {
        cleanupAutoUpdate();
    }

    // Use autoUpdate to automatically update position
    cleanupAutoUpdate = autoUpdate(box, tooltip, () => {
        computePosition(box, tooltip, {
            placement: 'right',
            middleware: [offset(8), flip(), shift({ padding: 5 })],
        }).then(({ x, y }) => {
            tooltip.style.left = `${x}px`;
            tooltip.style.top = `${y}px`;
        });
    });
}

/**
 * Hides the tooltip immediately
 */
function hideTooltip(tooltip: HTMLElement) {
    tooltip.style.opacity = '0';
    tooltip.style.transform = 'scale(0.95)';

    // Clean up autoUpdate when tooltip is hidden
    if (cleanupAutoUpdate) {
        cleanupAutoUpdate();
        cleanupAutoUpdate = null;
    }
}

/**
 * Formats duration in minutes to human readable format
 */
function formatDuration(durationMinutes: number): string {
    const hours = Math.floor(durationMinutes / 60);
    const minutes = durationMinutes % 60;
    return hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
}

/**
 * Creates tooltip content for an activity period
 */
function createTooltipContent(
    status: string,
    durationText: string,
    windowActivities?: WindowActivityInPeriod[]
): string | HTMLElement {
    if (!windowActivities || windowActivities.length === 0) {
        return `${status} (${durationText})`;
    }

    const container = document.createElement('div');
    container.style.maxWidth = '300px';

    // Header with status and duration
    const header = document.createElement('div');
    header.style.fontWeight = '600';
    header.style.marginBottom = '8px';
    header.textContent = `${status} (${durationText})`;
    container.appendChild(header);

    // Window activities list
    const totalActivities = windowActivities.reduce((sum, act) => sum + act.count, 0);

    // Show top 5 activities
    const topActivities = windowActivities.slice(0, 5);

    topActivities.forEach((activity) => {
        const activityDiv = document.createElement('div');
        activityDiv.style.marginTop = '4px';
        activityDiv.style.fontSize = '11px';
        activityDiv.style.opacity = '0.9';
        activityDiv.style.display = 'flex';
        activityDiv.style.alignItems = 'center';
        activityDiv.style.gap = '6px';

        // Add icon if available
        if (activity.icon) {
            const icon = document.createElement('img');
            icon.src = activity.icon;
            icon.alt = activity.appName;
            icon.style.width = '16px';
            icon.style.height = '16px';
            icon.style.borderRadius = '2px';
            icon.style.flexShrink = '0';
            activityDiv.appendChild(icon);
        } else {
            // Placeholder for no icon
            const placeholder = document.createElement('div');
            placeholder.style.width = '16px';
            placeholder.style.height = '16px';
            placeholder.style.borderRadius = '2px';
            placeholder.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
            placeholder.style.display = 'flex';
            placeholder.style.alignItems = 'center';
            placeholder.style.justifyContent = 'center';
            placeholder.style.fontSize = '8px';
            placeholder.style.flexShrink = '0';
            placeholder.textContent = activity.appName.charAt(0).toUpperCase();
            activityDiv.appendChild(placeholder);
        }

        const textSpan = document.createElement('span');
        textSpan.style.flex = '1';
        textSpan.style.overflow = 'hidden';
        textSpan.style.textOverflow = 'ellipsis';
        textSpan.style.whiteSpace = 'nowrap';

        const percentage = ((activity.count / totalActivities) * 100).toFixed(0);
        const activityText = activity.url
            ? `${activity.appName} - ${activity.url}`
            : activity.appName;

        textSpan.textContent = `${percentage}% ${activityText}`;
        activityDiv.appendChild(textSpan);

        container.appendChild(activityDiv);
    });

    // Show "and X more" if there are more activities
    if (windowActivities.length > 5) {
        const moreDiv = document.createElement('div');
        moreDiv.style.marginTop = '4px';
        moreDiv.style.fontSize = '11px';
        moreDiv.style.opacity = '0.7';
        moreDiv.style.fontStyle = 'italic';
        moreDiv.textContent = `...and ${windowActivities.length - 5} more`;
        container.appendChild(moreDiv);
    }

    return container;
}

/**
 * Renders activity status boxes in the calendar time grid
 */
export function renderActivityStatusBoxes(
    calendarEl: HTMLElement,
    activityPeriods: ActivityPeriod[]
) {
    if (!calendarEl) return;

    // Clean up existing activity boxes
    const existingBoxes = calendarEl.querySelectorAll('.activity-status-box');
    existingBoxes.forEach((box) => box.remove());

    // Remove has-activity-status class from all lanes
    const allLanes = calendarEl.querySelectorAll('.fc-timegrid-col');
    allLanes.forEach((lane) => lane.classList.remove('has-activity-status'));

    const timeGrid = calendarEl.querySelector('.fc-timegrid-body');
    if (!timeGrid) return;

    const lanes = timeGrid.querySelectorAll('.fc-timegrid-col');
    if (lanes.length === 0) return;

    // Get or reuse the single tooltip instance
    const tooltip = getOrCreateTooltip();

    // Get slot duration from calendar (fallback to 15 minutes)
    const slotDurationMinutes = getSlotDuration(calendarEl);

    lanes.forEach((lane: Element) => {
        // Get the date for this lane from the data attribute
        const laneEl = lane as HTMLElement;
        const dateStr = laneEl.getAttribute('data-date');

        if (!dateStr) return;

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

            // Calculate actual start and end times for this day
            const actualStart = periodStart > laneDateStart ? periodStart : laneDateStart;
            const actualEnd = periodEnd < laneDateEnd ? periodEnd : laneDateEnd;

            // Calculate the position and height of the activity box
            const { top, height } = calculateBoxPosition(
                calendarEl,
                actualStart,
                actualEnd,
                slotDurationMinutes
            );

            if (height <= 0) return;

            hasActivityStatusForThisDay = true;

            // Calculate duration in minutes
            const durationMs = actualEnd.getTime() - actualStart.getTime();
            const durationMinutes = Math.round(durationMs / 60000);
            const durationText = formatDuration(durationMinutes);

            // Add tooltip text based on status
            const status = period.isIdle ? 'Idling' : 'Active';

            // Create and append the activity status box
            const box = document.createElement('div');
            box.className = `activity-status-box ${period.isIdle ? 'idle' : 'active'}`;
            box.style.top = `${top}px`;
            box.style.height = `${height}px`;

            // Store tooltip content generator in data attribute for event delegation
            const tooltipContent = createTooltipContent(
                status,
                durationText,
                period.windowActivities
            );

            // Add hover event listeners for tooltip
            box.addEventListener('mouseenter', () => {
                showTooltip(box, tooltip, tooltipContent);
            });

            box.addEventListener('mouseleave', () => {
                hideTooltip(tooltip);
            });

            // Position relative to the lane
            const laneFrame = lane.querySelector('.fc-timegrid-col-frame');
            if (laneFrame) {
                laneFrame.appendChild(box);
            }
        });

        // Mark this lane as having activity status if any periods were rendered
        if (hasActivityStatusForThisDay) {
            laneEl.classList.add('has-activity-status');
        }
    });
}

/**
 * Gets the slot duration from the calendar configuration
 */
function getSlotDuration(calendarEl: HTMLElement): number {
    const slotsEl = calendarEl.querySelectorAll('.fc-timegrid-slot');
    if (slotsEl.length < 2) return 15; // Default to 15 minutes

    // Try to calculate from the time difference between slots
    const firstSlot = slotsEl[0] as HTMLElement;
    const secondSlot = slotsEl[1] as HTMLElement;

    const firstTime = firstSlot.getAttribute('data-time');
    const secondTime = secondSlot.getAttribute('data-time');

    if (firstTime && secondTime) {
        const [h1 = 0, m1 = 0] = firstTime.split(':').map(Number);
        const [h2 = 0, m2 = 0] = secondTime.split(':').map(Number);
        const diff = h2 * 60 + m2 - (h1 * 60 + m1);
        if (diff > 0) return diff;
    }

    // Fallback to 15 minutes
    return 15;
}

/**
 * Calculates the pixel position and height for an activity status box
 */
function calculateBoxPosition(
    calendarEl: HTMLElement,
    startTime: Date,
    endTime: Date,
    slotDurationMinutes: number
): { top: number; height: number } {
    // Get the slot duration and slot height
    const slotsEl = calendarEl.querySelectorAll('.fc-timegrid-slot');
    if (slotsEl.length === 0) {
        return { top: 0, height: 0 };
    }

    // Calculate slot height (assuming all slots are equal height)
    const firstSlot = slotsEl[0] as HTMLElement;
    const slotHeight = firstSlot.offsetHeight;

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
 * Cleanup function to remove tooltip from DOM
 */
export function cleanupActivityStatusPlugin() {
    if (tooltipInstance) {
        tooltipInstance.remove();
        tooltipInstance = null;
    }
    if (cleanupAutoUpdate) {
        cleanupAutoUpdate();
        cleanupAutoUpdate = null;
    }
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
