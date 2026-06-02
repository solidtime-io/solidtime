import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import TimesheetCell from './TimesheetCell.vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import type { TimesheetCell as TimesheetCellType } from '@/utils/useTimesheetGrid';

function buildCell(totalSeconds: number): TimesheetCellType {
    return {
        dayIndex: 0,
        date: '2026-04-13',
        entries: [],
        totalSeconds,
    };
}

function mountTimesheetCell(totalSeconds = 2 * 3600) {
    return mount(TimesheetCell, {
        props: {
            cell: buildCell(totalSeconds),
            dayIndex: 0,
            date: '2026-04-13',
            isToday: false,
            hasRunningEntry: false,
        },
    });
}

describe('TimesheetCell', () => {
    it('emits 0 when the cleared value is committed on blur', async () => {
        const wrapper = mountTimesheetCell();
        const input = wrapper.get('input');

        await input.trigger('focus');
        await input.setValue('');
        await input.trigger('blur');

        expect(wrapper.emitted('update')).toEqual([[0]]);
    });

    it('emits 0 when the cleared value is committed with Enter', async () => {
        const wrapper = mountTimesheetCell();
        const input = wrapper.get('input');

        await input.trigger('focus');
        await input.setValue('');
        await input.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('update')).toEqual([[0]]);
    });

    it('restores the previous value and emits nothing on Escape', async () => {
        const wrapper = mountTimesheetCell();
        const input = wrapper.get('input');
        const previousValue = formatHumanReadableDuration(2 * 3600, 'hours-minutes', 'point');

        await input.trigger('focus');
        await input.setValue('');
        await input.trigger('keydown', { key: 'Escape' });
        await nextTick();

        expect(wrapper.emitted('update')).toBeUndefined();
        expect((input.element as HTMLInputElement).value).toBe(previousValue);
    });

    it('shows a pending 0 (delete in flight) over the cell total', () => {
        const wrapper = mount(TimesheetCell, {
            props: {
                cell: buildCell(2 * 3600),
                dayIndex: 0,
                date: '2026-04-13',
                isToday: false,
                hasRunningEntry: false,
                pendingSeconds: 0,
            },
        });

        // `??` (not `||`): a pending 0 must win over the 2h cell total.
        expect((wrapper.get('input').element as HTMLInputElement).value).toBe('');
    });

    it('disables editing while the cell is saving', () => {
        const wrapper = mount(TimesheetCell, {
            props: {
                cell: buildCell(2 * 3600),
                dayIndex: 0,
                date: '2026-04-13',
                isToday: false,
                hasRunningEntry: false,
                saveStatus: 'saving',
            },
        });

        expect((wrapper.get('input').element as HTMLInputElement).disabled).toBe(true);
    });
});
