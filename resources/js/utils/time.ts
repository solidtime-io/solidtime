import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';

dayjs.extend(duration);

export function formatHumanReadableDuration(duration: number): string {
    return dayjs.duration(duration, 's').format('HH[h] mm[min]');
}
