import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import relativeTime from 'dayjs/plugin/relativeTime';
import isToday from 'dayjs/plugin/isToday';
import isYesterday from 'dayjs/plugin/isYesterday';
import utc from 'dayjs/plugin/utc';

dayjs.extend(relativeTime);
dayjs.extend(isToday);
dayjs.extend(isYesterday);
dayjs.extend(duration);
dayjs.extend(utc);

export function formatHumanReadableDuration(duration: number): string {
    return dayjs.duration(duration, 's').format('HH[h] mm[min]');
}

export function calculateDifference(start: string, end: string | null) {
    if (end === null) {
        end = dayjs().utc().format();
    }
    return dayjs(end).diff(dayjs(start), 'second');
}
export function formatTime(date: string) {
    return dayjs(date).utc().format('HH:mm');
}

export function formatDate(date: string): string {
    return dayjs(date).utc().format('DD.MM.YYYY');
}

export function formatHumanReadableDate(date: string) {
    if (dayjs(date).isToday()) {
        return 'Today';
    } else if (dayjs(date).isYesterday()) {
        return 'Yesterday';
    }
    return dayjs(date).fromNow();
}
