import dayjs from 'dayjs';
import { afterEach, describe, expect, test, vi } from 'vitest';
import { getDayJsInstance, getLocalizedDayJs } from './time';
import { getDateRangeLabel, shiftDateRange } from './dateRange';

const reference = dayjs('2026-09-21');

afterEach(() => {
    vi.mocked(window.getTimezoneSetting).mockReturnValue('UTC');
});

describe('getDateRangeLabel', () => {
    test('labels relative weeks', () => {
        getDayJsInstance();
        expect(
            getDateRangeLabel(reference.startOf('week'), reference.endOf('week'), {
                referenceDate: reference,
            })
        ).toBe('This Week');
        expect(
            getDateRangeLabel(
                reference.subtract(1, 'week').startOf('week'),
                reference.subtract(1, 'week').endOf('week'),
                { referenceDate: reference }
            )
        ).toBe('Last Week');
    });

    test('labels common rolling ranges', () => {
        expect(
            getDateRangeLabel(reference.subtract(13, 'day'), reference.endOf('day'), {
                referenceDate: reference,
            })
        ).toBe('Last 14 Days');
    });

    test('labels full calendar months and years', () => {
        expect(
            getDateRangeLabel(dayjs('2025-09-01'), dayjs('2025-09-30'), {
                referenceDate: reference,
            })
        ).toBe('September 2025');
        expect(
            getDateRangeLabel(dayjs('2024-01-01'), dayjs('2024-12-31'), {
                referenceDate: reference,
            })
        ).toBe('2024');
    });

    test('keeps relative labels for the current periods', () => {
        expect(
            getDateRangeLabel(reference.startOf('month'), reference.endOf('month'), {
                referenceDate: reference,
            })
        ).toBe('This Month');
        expect(
            getDateRangeLabel(reference.startOf('year'), reference.endOf('year'), {
                referenceDate: reference,
            })
        ).toBe('This Year');
    });

    test('uses the organization date format for custom ranges', () => {
        expect(
            getDateRangeLabel(dayjs('2026-08-03'), dayjs('2026-08-12'), {
                dateFormat: 'slash-separated-dd-mm-yyyy',
                referenceDate: reference,
            })
        ).toBe('03/08/2026 – 12/08/2026');
    });

    test('renders a single historical day without duplicating it', () => {
        expect(
            getDateRangeLabel(dayjs('2026-08-03'), dayjs('2026-08-03'), {
                referenceDate: reference,
            })
        ).toBe('Aug 3, 2026');
    });
});

describe('shiftDateRange', () => {
    test('moves full weeks as calendar periods', () => {
        getDayJsInstance();
        const shifted = shiftDateRange(reference.startOf('week'), reference.endOf('week'), -1);
        expect(shifted.start.format('YYYY-MM-DD')).toBe('2026-09-14');
        expect(shifted.end.format('YYYY-MM-DD')).toBe('2026-09-20');
    });

    test('moves full months without truncating longer months', () => {
        const shifted = shiftDateRange(dayjs('2026-02-01'), dayjs('2026-02-28'), 1);
        expect(shifted.start.format('YYYY-MM-DD')).toBe('2026-03-01');
        expect(shifted.end.format('YYYY-MM-DD')).toBe('2026-03-31');
    });

    test('moves custom ranges by their inclusive duration', () => {
        const shifted = shiftDateRange(dayjs('2026-09-01'), dayjs('2026-09-14'), -1);
        expect(shifted.start.format('YYYY-MM-DD')).toBe('2026-08-18');
        expect(shifted.end.format('YYYY-MM-DD')).toBe('2026-08-31');
    });

    test('normalizes custom range offsets across the spring DST transition', () => {
        vi.mocked(window.getTimezoneSetting).mockReturnValue('Europe/Vienna');
        const shifted = shiftDateRange(
            getLocalizedDayJs('2026-03-14T23:00:00Z'),
            getLocalizedDayJs('2026-03-27T23:00:00Z'),
            1
        );

        expect(shifted.start.format()).toBe('2026-03-29T00:00:00+01:00');
        expect(shifted.end.format()).toBe('2026-04-11T23:59:59+02:00');
    });

    test('normalizes custom range offsets across the autumn DST transition', () => {
        vi.mocked(window.getTimezoneSetting).mockReturnValue('Europe/Vienna');
        const shifted = shiftDateRange(
            getLocalizedDayJs('2026-10-17T22:00:00Z'),
            getLocalizedDayJs('2026-10-30T23:00:00Z'),
            1
        );

        expect(shifted.start.format()).toBe('2026-11-01T00:00:00+01:00');
        expect(shifted.end.format()).toBe('2026-11-14T23:59:59+01:00');
    });
});
