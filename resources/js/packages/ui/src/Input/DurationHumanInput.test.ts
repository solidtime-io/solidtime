import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { computed } from 'vue';
import DurationHumanInput from './DurationHumanInput.vue';

enableAutoUnmount(afterEach);

function mountInput(props: { start: string; end: string; anchor?: 'start' | 'end' }) {
    return mount(DurationHumanInput, {
        props,
        global: {
            provide: {
                organization: computed(() => ({ interval_format: 'hours-minutes' })),
            },
        },
    });
}

describe('DurationHumanInput', () => {
    it.each(['blur', 'keydown.enter'])(
        'preserves the end by default when applying duration via %s',
        async (event) => {
            const wrapper = mountInput({
                start: '2026-10-26T08:00:00Z',
                end: '2026-10-26T09:00:00Z',
            });

            await wrapper.get('input').setValue('8h');
            await wrapper.get('input').trigger(event);

            expect(wrapper.emitted('update:start')).toEqual([['2026-10-26T01:00:00Z']]);
            expect(wrapper.emitted('update:end')).toBeUndefined();
        }
    );

    it.each([
        ['same day', '2026-10-26T09:00:00+01:00', '2026-10-26T16:00:00Z'],
        ['overnight', '2026-10-26T22:00:00+01:00', '2026-10-27T05:00:00Z'],
        ['spring DST change', '2026-03-29T00:00:00+01:00', '2026-03-29T07:00:00Z'],
        ['autumn DST change', '2026-10-25T00:00:00+02:00', '2026-10-25T06:00:00Z'],
    ])('adds 8 elapsed hours to the fixed start: %s', async (_, start, expectedEnd) => {
        const wrapper = mountInput({
            start,
            end: '2026-10-26T09:00:00Z',
            anchor: 'start',
        });

        await wrapper.get('input').setValue('8h');
        await wrapper.get('input').trigger('blur');

        expect(wrapper.emitted('update:end')).toEqual([[expectedEnd]]);
        expect(wrapper.emitted('update:start')).toBeUndefined();
    });
});
