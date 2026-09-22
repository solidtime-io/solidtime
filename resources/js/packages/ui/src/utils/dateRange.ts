import type { Dayjs } from 'dayjs';
import { formatDate, getDayJsInstance, getLocalizedDayJs, type DateFormat } from './time';

export type DateRangeDirection = -1 | 1;

function isSameDay(left: Dayjs, right: Dayjs): boolean {
    return left.isSame(right, 'day');
}

function isRange(start: Dayjs, end: Dayjs, expectedStart: Dayjs, expectedEnd: Dayjs): boolean {
    return isSameDay(start, expectedStart) && isSameDay(end, expectedEnd);
}

function formatCompactRange(start: Dayjs, end: Dayjs): string {
    if (start.year() !== end.year()) {
        return `${start.format('MMM D, YYYY')} – ${end.format('MMM D, YYYY')}`;
    }
    if (start.month() !== end.month()) {
        return `${start.format('MMM D')} – ${end.format('MMM D, YYYY')}`;
    }
    return `${start.format('MMM D')} – ${end.format('D, YYYY')}`;
}

/**
 * Returns a human-friendly label for an inclusive date range. Relative labels
 * are intentionally shared by reporting, the timesheet, and the calendar.
 */
export function getDateRangeLabel(
    start: Dayjs,
    end: Dayjs,
    options: { dateFormat?: DateFormat; referenceDate?: Dayjs } = {}
): string {
    // Ensure the configured first day of the week is applied before using startOf('week').
    getDayJsInstance();

    const reference = (options.referenceDate ?? getLocalizedDayJs()).startOf('day');
    const rangeStart = start.startOf('day');
    const rangeEnd = end.startOf('day');

    const relativeDays: Array<[number, string]> = [
        [0, 'Today'],
        [-1, 'Yesterday'],
        [1, 'Tomorrow'],
    ];
    for (const [offset, label] of relativeDays) {
        const day = reference.add(offset, 'day');
        if (isRange(rangeStart, rangeEnd, day, day)) return label;
    }

    const relativePeriods: Array<['week' | 'month' | 'year', number, string]> = [
        ['week', 0, 'This Week'],
        ['week', -1, 'Last Week'],
        ['week', 1, 'Next Week'],
        ['month', 0, 'This Month'],
        ['month', -1, 'Last Month'],
        ['month', 1, 'Next Month'],
        ['year', 0, 'This Year'],
        ['year', -1, 'Last Year'],
        ['year', 1, 'Next Year'],
    ];
    for (const [unit, offset, label] of relativePeriods) {
        const period = reference.add(offset, unit);
        if (isRange(rangeStart, rangeEnd, period.startOf(unit), period.endOf(unit))) return label;
    }

    // Named calendar periods outside the relative windows above, e.g. "May 2025"
    // for a full month and "2025" for a full year.
    const monthStart = rangeStart.startOf('month');
    if (isRange(rangeStart, rangeEnd, monthStart, monthStart.endOf('month'))) {
        return rangeStart.format('MMMM YYYY');
    }
    const yearStart = rangeStart.startOf('year');
    if (isRange(rangeStart, rangeEnd, yearStart, yearStart.endOf('year'))) {
        return rangeStart.format('YYYY');
    }

    const rollingRanges: Array<[number, string]> = [
        [14, 'Last 14 Days'],
        [30, 'Last 30 Days'],
        [90, 'Last 90 Days'],
    ];
    for (const [days, label] of rollingRanges) {
        if (isRange(rangeStart, rangeEnd, reference.subtract(days - 1, 'day'), reference)) {
            return label;
        }
    }
    if (isRange(rangeStart, rangeEnd, reference.subtract(12, 'month'), reference)) {
        return 'Last 12 Months';
    }

    if (isSameDay(rangeStart, rangeEnd)) {
        return options.dateFormat
            ? formatDate(rangeStart.format('YYYY-MM-DD'), options.dateFormat)
            : rangeStart.format('MMM D, YYYY');
    }

    if (options.dateFormat) {
        return `${formatDate(rangeStart.format('YYYY-MM-DD'), options.dateFormat)} – ${formatDate(
            rangeEnd.format('YYYY-MM-DD'),
            options.dateFormat
        )}`;
    }
    return formatCompactRange(rangeStart, rangeEnd);
}

/** Shift an inclusive range by one period, preserving full calendar periods. */
export function shiftDateRange(
    start: Dayjs,
    end: Dayjs,
    direction: DateRangeDirection
): { start: Dayjs; end: Dayjs } {
    getDayJsInstance();

    const rangeStart = start.startOf('day');
    const rangeEnd = end.endOf('day');

    if (isRange(rangeStart, rangeEnd, rangeStart.startOf('year'), rangeStart.endOf('year'))) {
        const shiftedStart = rangeStart.add(direction, 'year').startOf('year');
        return { start: shiftedStart, end: shiftedStart.endOf('year') };
    }
    if (isRange(rangeStart, rangeEnd, rangeStart.startOf('month'), rangeStart.endOf('month'))) {
        const shiftedStart = rangeStart.add(direction, 'month').startOf('month');
        return { start: shiftedStart, end: shiftedStart.endOf('month') };
    }
    if (isRange(rangeStart, rangeEnd, rangeStart.startOf('week'), rangeStart.endOf('week'))) {
        const shiftedStart = rangeStart.add(direction, 'week').startOf('week');
        return { start: shiftedStart, end: shiftedStart.endOf('week') };
    }

    const days = rangeEnd.startOf('day').diff(rangeStart, 'day') + 1;
    return {
        start: rangeStart.add(days * direction, 'day').startOf('day'),
        end: rangeEnd.add(days * direction, 'day').endOf('day'),
    };
}
