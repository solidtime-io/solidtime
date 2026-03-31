import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import isToday from 'dayjs/plugin/isToday';
import isYesterday from 'dayjs/plugin/isYesterday';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import weekOfYear from 'dayjs/plugin/weekOfYear';
import parse from 'parse-duration';

import { getUserTimezone, getWeekStart } from './settings';
import updateLocale from 'dayjs/plugin/updateLocale';
import { computed } from 'vue';
import { formatNumber } from './number';

export type DateFormat =
    | 'point-separated-d-m-yyyy'
    | 'slash-separated-mm-dd-yyyy'
    | 'slash-separated-dd-mm-yyyy'
    | 'hyphen-separated-dd-mm-yyyy'
    | 'hyphen-separated-mm-dd-yyyy'
    | 'hyphen-separated-yyyy-mm-dd';

// Day of week index type for calendar components (0 = Sunday, 6 = Saturday)
export type WeekStartDay = 0 | 1 | 2 | 3 | 4 | 5 | 6;

const dateFormatMap: Record<DateFormat, string> = {
    'point-separated-d-m-yyyy': 'D.M.YYYY',
    'slash-separated-mm-dd-yyyy': 'MM/DD/YYYY',
    'slash-separated-dd-mm-yyyy': 'DD/MM/YYYY',
    'hyphen-separated-dd-mm-yyyy': 'DD-MM-YYYY',
    'hyphen-separated-mm-dd-yyyy': 'MM-DD-YYYY',
    'hyphen-separated-yyyy-mm-dd': 'YYYY-MM-DD',
};

export type TimeFormat = '12-hours' | '24-hours';
export type IntervalFormat =
    | 'decimal'
    | 'hours-minutes'
    | 'hours-minutes-colon-separated'
    | 'hours-minutes-seconds-colon-separated';
function configureParseLocale(numberFormat?: string) {
    switch (numberFormat) {
        case 'point-comma':
            parse.unit.group = '.';
            parse.unit.decimal = ',';
            break;
        case 'space-comma':
            parse.unit.group = ' ';
            parse.unit.decimal = ',';
            break;
        case 'space-point':
            parse.unit.group = ' ';
            parse.unit.decimal = '.';
            break;
        case 'apostrophe-point':
            parse.unit.group = "'";
            parse.unit.decimal = '.';
            break;
        default:
            // 'comma-point' or unset — default English
            parse.unit.group = ',';
            parse.unit.decimal = '.';
            break;
    }
}

dayjs.extend(relativeTime);
dayjs.extend(isToday);
dayjs.extend(isYesterday);
dayjs.extend(duration);
dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(updateLocale);
dayjs.extend(weekOfYear);

export function getDayJsInstance() {
    dayjs.updateLocale('en', {
        weekStart: firstDayIndex.value,
    });
    return dayjs;
}

export const firstDayIndex = computed(() => {
    const apiDayOrder = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];
    return apiDayOrder.indexOf(getWeekStart());
});

export function formatHumanReadableDuration(
    duration: number,
    intervalFormat?: string,
    numberFormat?: string
): string {
    const dayJsDuration = dayjs.duration(duration, 's');
    const hours = Math.floor(dayJsDuration.asHours());
    const minutes = dayJsDuration.minutes();
    const seconds = dayJsDuration.seconds();

    switch (intervalFormat) {
        case 'decimal':
            return formatNumber(dayJsDuration.asHours(), numberFormat) + ' h';
        case 'hours-minutes':
            return `${hours}h ${minutes.toString().padStart(2, '0')}min`;
        case 'hours-minutes-colon-separated':
            return `${hours}:${minutes.toString().padStart(2, '0')}`;
        case 'hours-minutes-seconds-colon-separated':
            return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        default:
            return `${hours}h ${minutes.toString().padStart(2, '0')}min`;
    }
}

export function formatDuration(duration: number): string {
    const dayJsDuration = dayjs.duration(duration, 's');
    const hours = Math.floor(dayJsDuration.asHours());
    const minutes = dayJsDuration.minutes();
    const seconds = dayJsDuration.seconds();
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

export function calculateDifference(start: string, end: string | null) {
    if (end === null) {
        end = dayjs().utc().format();
    }
    return dayjs(end).diff(dayjs(start), 'second');
}

/**
 * Returns a formatted time.
 * @param date - A UTC date time string.
 * @param timeFormat - The time format to use ('12-hours' or '24-hours')
 */
export function formatTime(date: string, timeFormat: TimeFormat = '24-hours') {
    const format = timeFormat === '12-hours' ? 'hh:mm A' : 'HH:mm';
    return dayjs.utc(date).tz(getUserTimezone()).format(format);
}

export function getLocalizedDayJs(timestamp?: string | null) {
    return dayjs.utc(timestamp).tz(getUserTimezone());
}

/**
 * Create a dayjs instance for a specific wall-clock time on a given day.
 * Sets hour/minute directly to avoid DST issues with .add(minutes) on
 * transition days. Negative or overflow values are normalised by shifting
 * whole days (`.add(n, 'day')` is DST-safe).
 */
export function getLocalizedDayJsFromMinutes(dayStr: string, minutesFromMidnight: number) {
    const dayOffset = Math.floor(minutesFromMidnight / (24 * 60));
    const remainder = minutesFromMidnight - dayOffset * 24 * 60;
    return dayjs
        .tz(`${dayStr}T00:00:00`, getUserTimezone())
        .add(dayOffset, 'day')
        .hour(Math.floor(remainder / 60))
        .minute(Math.round(remainder % 60))
        .second(0);
}

export function getLocalizedDateFromTimestamp(timestamp: string) {
    return getLocalizedDayJs(timestamp).format('YYYY-MM-DD');
}

/*
 * Returns a formatted date.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
export function formatDate(date: string, format: DateFormat = 'point-separated-d-m-yyyy'): string {
    if (date?.includes('+')) {
        console.warn('Date contains timezone information, use formatDateLocalized instead');
    }
    return getDayJsInstance()(date).format(dateFormatMap[format]);
}

/*
 * Returns a formatted date.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
export function formatDateLocalized(
    date: string,
    format: DateFormat = 'point-separated-d-m-yyyy'
): string {
    return getLocalizedDayJs(date).format(dateFormatMap[format]);
}

export function formatDateTimeLocalized(
    date: string,
    dateFormat?: DateFormat,
    timeFormat?: TimeFormat
): string {
    const format = `${dateFormatMap[dateFormat ?? 'point-separated-d-m-yyyy']} ${timeFormat === '12-hours' ? 'hh:mm A' : 'HH:mm'}`;
    return getLocalizedDayJs(date).format(format);
}

export function formatWeek(date: string | null): string {
    return 'Week ' + getDayJsInstance()(date).week();
}

/*
 * Returns a human readable date format.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
export function formatHumanReadableDate(date: string) {
    const dateObj = dayjs(date);
    const today = dayjs();

    if (dateObj.isToday()) {
        return 'Today';
    } else if (dateObj.isYesterday()) {
        return 'Yesterday';
    }

    // Calculate difference in days
    const diffInDays = today.diff(dateObj, 'day');

    if (diffInDays > 0 && diffInDays <= 30) {
        // For dates in the past (2-30 days ago)
        return `${diffInDays} ${diffInDays === 1 ? 'day' : 'days'} ago`;
    } else if (diffInDays < 0 && diffInDays >= -30) {
        // For dates in the future (within 30 days)
        const futureDays = Math.abs(diffInDays);
        return `In ${futureDays} ${futureDays === 1 ? 'day' : 'days'}`;
    }

    // For dates older than 30 days, show the actual date
    return dateObj.format('MMM D, YYYY');
}

export function formatWeekday(date: string) {
    return dayjs(date).format('dddd');
}

export function formatStartEnd(
    start: string,
    end: string | null,
    timeFormat: TimeFormat = '24-hours'
) {
    if (end) {
        return `${formatTime(start, timeFormat)} - ${formatTime(end, timeFormat)}`;
    } else {
        return `${formatTime(start, timeFormat)} - ...`;
    }
}

export function parseTimeInput(
    input: string,
    numberFormat?: string,
    defaultUnit: 'minutes' | 'hours' = 'minutes'
): number | null {
    configureParseLocale(numberFormat);

    // Check if input is a decimal number (hours)
    const decimalRegex = /^-?\d+[.,]\d+$/;
    if (decimalRegex.test(input)) {
        const hours = parseFloat(input.replace(',', '.'));
        return Math.round(hours * 3600);
    }

    // Check if input is just a number
    if (/^-?\d+$/.test(input)) {
        const value = parseInt(input);
        return defaultUnit === 'hours' ? value * 3600 : value * 60;
    }

    // Check if input is in HH:MM:SS format
    const HHMMSStimeRegex = /^([0-9]{1,2}):([0-5]?[0-9]):([0-5]?[0-9])$/;
    if (HHMMSStimeRegex.test(input)) {
        const match = input.match(HHMMSStimeRegex);
        if (match) {
            const hours = parseInt(match[1]!);
            const minutes = parseInt(match[2]!);
            const seconds = parseInt(match[3]!);
            return hours * 3600 + minutes * 60 + seconds;
        }
    }

    // Check if input is in HH:MM format
    const HHMMtimeRegex = /^([0-9]{1,2}):([0-5]?[0-9])$/;
    if (HHMMtimeRegex.test(input)) {
        const match = input.match(HHMMtimeRegex);
        if (match) {
            const hours = parseInt(match[1]!);
            const minutes = parseInt(match[2]!);
            return (hours * 60 + minutes) * 60;
        }
    }

    // Try to parse natural language like "1h 30m" or locale-formatted like "1,00 h"
    const parsedDuration = parse(input, 's');
    if (parsedDuration && parsedDuration > 0) {
        return parsedDuration;
    }

    return null;
}
