import { computed, ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { TimeEntry } from '@/packages/api/src';
import type { CalendarEvent } from './calendarTypes';
import { useContextMenu } from './useContextMenu';

function breakEntry(): TimeEntry {
    return {
        id: 'break-1',
        start: '2026-07-14T10:00:00Z',
        end: '2026-07-14T11:00:00Z',
        duration: 3600,
        description: 'Lunch',
        project_id: null,
        task_id: null,
        organization_id: 'organization-1',
        user_id: 'user-1',
        tags: [],
        billable: false,
        type: 'break',
    } as TimeEntry;
}

describe('useContextMenu break actions', () => {
    const createTimeEntry = vi.fn().mockResolvedValue(undefined);
    const updateTimeEntry = vi.fn().mockResolvedValue(undefined);

    beforeEach(() => {
        vi.clearAllMocks();
    });

    function contextMenu() {
        const entry = breakEntry();
        const calendarEvents = computed(() => [
            {
                id: entry.id,
                timeEntry: entry,
            } as CalendarEvent,
        ]);
        const menu = useContextMenu({
            calendarSettings: ref({
                snapMinutes: 15,
                startHour: 0,
                endHour: 24,
                slotMinutes: 15,
            }),
            calendarEvents,
            pixelsToMinutesFromMidnight: () => 0,
            getDayFromClientX: () => null,
            clientYToGridPixels: () => 0,
            createTimeEntry,
            updateTimeEntry,
            deleteTimeEntry: vi.fn().mockResolvedValue(undefined),
            onEditEvent: vi.fn(),
            onCreateEvent: vi.fn(),
            onCreateBreak: vi.fn(),
            emitRefresh: vi.fn(),
        });

        menu.handleCalendarContextMenu({
            target: {
                closest: () => ({
                    getAttribute: () => entry.id,
                }),
            },
        } as unknown as MouseEvent);

        return menu;
    }

    it('preserves the type when duplicating a break', async () => {
        await contextMenu().handleContextDuplicate();

        expect(createTimeEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'break',
            })
        );
    });

    it('preserves the type when creating the second half of a split break', async () => {
        await contextMenu().handleContextSplit();

        expect(updateTimeEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'break',
            })
        );
        expect(createTimeEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                type: 'break',
            })
        );
    });
});
