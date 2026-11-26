import { getDayJsInstance } from '@/packages/ui/src/utils/time';

/**
 * Current UTC offset of a timezone, for example "+01:00" for Europe/Vienna in winter.
 */
export function timezoneOffset(timezone: string): string {
    try {
        return getDayJsInstance()().tz(timezone).format('Z');
    } catch {
        return '+00:00';
    }
}
