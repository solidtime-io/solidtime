import { computed, ref } from 'vue';
import { describe, expect, test } from 'vitest';
import { useExternalEvents, type ExternalCalendarEvent } from './useExternalEvents';
import { getLocalizedDayJs } from '../utils/time';

describe('useExternalEvents', () => {
    const calendarSettings = ref({
        snapMinutes: 15,
        startHour: 8,
        endHour: 18,
        slotMinutes: 15,
    });

    const viewDays = computed(() => [getLocalizedDayJs('2026-08-01T00:00:00Z')]);

    test('places overlapping external events side-by-side', () => {
        const events: ExternalCalendarEvent[] = [
            {
                id: 'a',
                title: 'A',
                start: '2026-08-01T10:00:00Z',
                end: '2026-08-01T11:00:00Z',
                allDay: false,
                htmlLink: null,
            },
            {
                id: 'b',
                title: 'B',
                start: '2026-08-01T10:30:00Z',
                end: '2026-08-01T11:30:00Z',
                allDay: false,
                htmlLink: null,
            },
        ];

        const { externalEventsForDay } = useExternalEvents({
            externalEvents: () => events,
            viewDays,
            calendarSettings,
            minutesToPixels: (minutes) => minutes,
        });

        const dayEvents = externalEventsForDay('2026-08-01').sort((a, b) =>
            a.event.id.localeCompare(b.event.id)
        );

        expect(dayEvents).toHaveLength(2);
        expect(dayEvents.map((event) => event.left)).toEqual(['55%', '77.5%']);
        expect(dayEvents.map((event) => event.width)).toEqual(['22.5%', '22.5%']);
    });

    test('keeps full external width when events do not overlap', () => {
        const events: ExternalCalendarEvent[] = [
            {
                id: 'a',
                title: 'A',
                start: '2026-08-01T10:00:00Z',
                end: '2026-08-01T11:00:00Z',
                allDay: false,
                htmlLink: null,
            },
            {
                id: 'b',
                title: 'B',
                start: '2026-08-01T11:00:00Z',
                end: '2026-08-01T12:00:00Z',
                allDay: false,
                htmlLink: null,
            },
        ];

        const { externalEventsForDay } = useExternalEvents({
            externalEvents: () => events,
            viewDays,
            calendarSettings,
            minutesToPixels: (minutes) => minutes,
        });

        const dayEvents = externalEventsForDay('2026-08-01').sort((a, b) =>
            a.event.id.localeCompare(b.event.id)
        );

        expect(dayEvents).toHaveLength(2);
        expect(dayEvents.map((event) => event.left)).toEqual(['55%', '55%']);
        expect(dayEvents.map((event) => event.width)).toEqual(['45%', '45%']);
    });
});
