import { describe, expect, test } from 'vitest';
import { formatHumanReadableDuration, formatReportingDuration, formatWeekRange } from './time';

const seconds = 14 * 3600 + 45 * 60 + 6; // 14h 45m 06s

describe('formatHumanReadableDuration', () => {
    test('decimal', () => {
        expect(formatHumanReadableDuration(seconds, 'decimal', 'comma-point')).toBe('14.75 h');
    });

    test('hours-minutes', () => {
        expect(formatHumanReadableDuration(seconds, 'hours-minutes')).toBe('14h 45min');
    });

    test('hours-minutes-colon-separated', () => {
        expect(formatHumanReadableDuration(seconds, 'hours-minutes-colon-separated')).toBe('14:45');
    });

    test('hours-minutes-seconds-colon-separated', () => {
        expect(formatHumanReadableDuration(seconds, 'hours-minutes-seconds-colon-separated')).toBe(
            '14:45:06'
        );
    });
});

describe('formatReportingDuration', () => {
    test('decimal', () => {
        expect(formatReportingDuration(seconds, 'decimal', 'comma-point')).toBe('14.75 h');
    });

    test('hours-minutes', () => {
        expect(formatReportingDuration(seconds, 'hours-minutes')).toBe('14:45:06');
    });

    test('hours-minutes-colon-separated', () => {
        expect(formatReportingDuration(seconds, 'hours-minutes-colon-separated')).toBe('14:45:06');
    });

    test('hours-minutes-seconds-colon-separated', () => {
        expect(formatReportingDuration(seconds, 'hours-minutes-seconds-colon-separated')).toBe(
            '14:45:06'
        );
    });
});

describe('formatWeekRange', () => {
    test('renders the six days following the given first day of the week', () => {
        expect(formatWeekRange('2026-07-27', 'slash-separated-dd-mm-yyyy')).toBe(
            '27/07/2026 - 02/08/2026'
        );
    });

    test('spans a month boundary', () => {
        expect(formatWeekRange('2026-07-27')).toBe('27.7.2026 - 2.8.2026');
    });

    test('spans a year boundary', () => {
        expect(formatWeekRange('2025-12-29', 'slash-separated-dd-mm-yyyy')).toBe(
            '29/12/2025 - 04/01/2026'
        );
    });
});
