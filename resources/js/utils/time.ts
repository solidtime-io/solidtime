import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import isToday from 'dayjs/plugin/isToday';
import isYesterday from 'dayjs/plugin/isYesterday';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import { getUserTimezone } from '@/utils/useUser';

dayjs.extend(relativeTime);
dayjs.extend(isToday);
dayjs.extend(isYesterday);
dayjs.extend(duration);
dayjs.extend(utc);
dayjs.extend(timezone);

export function getDayJsInstance() {
    return dayjs;
}

export function formatHumanReadableDuration(duration: number): string {
    const dayJsDuration = dayjs.duration(duration, 's');
    const hours = dayJsDuration.hours() + dayJsDuration.days() * 24;
    const minutes = dayJsDuration.minutes();
    return `${hours}h ${minutes.toString().padStart(2, '0')}min`;
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
 */
export function formatTime(date: string) {
    return dayjs.utc(date).tz(getUserTimezone()).format('HH:mm');
}

export function getLocalizedDayJs(timestamp: string | null) {
    return dayjs.utc(timestamp).tz(getUserTimezone());
}

export function getLocalizedDateFromTimestamp(timestamp: string) {
    return getLocalizedDayJs(timestamp).format('YYYY-MM-DD');
}

/*
 * Returns a formatted date.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
export function formatDate(date: string): string {
    return dayjs(date).format('DD.MM.YYYY');
}

/*
 * Returns a human readable date format.
 * @param date - date in the format of 'YYYY-MM-DD'
 */
export function formatHumanReadableDate(date: string) {
    if (dayjs(date).isToday()) {
        return 'Today';
    } else if (dayjs(date).isYesterday()) {
        return 'Yesterday';
    }
    return dayjs(date).fromNow();
}
