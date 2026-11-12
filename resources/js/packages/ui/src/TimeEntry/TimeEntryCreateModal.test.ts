import { enableAutoUnmount, mount } from '@vue/test-utils';
import { afterEach, assert, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, nextTick } from 'vue';
import DatePicker from '../Input/DatePicker.vue';
import TimeEntryCreateModal from './TimeEntryCreateModal.vue';

enableAutoUnmount(afterEach);

beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date('2026-10-26T08:00:00Z'));
    vi.mocked(window.getTimezoneSetting).mockReturnValue('Europe/Warsaw');
});

afterEach(() => {
    vi.useRealTimers();
    vi.mocked(window.getTimezoneSetting).mockReturnValue('UTC');
});

function mountModal() {
    const createTimeEntry = vi.fn().mockResolvedValue(undefined);
    const wrapper = mount(TimeEntryCreateModal, {
        props: {
            show: true,
            createTimeEntry,
            createClient: vi.fn(),
            createProject: vi.fn(),
            createTag: vi.fn(),
            tags: [],
            projects: [],
            tasks: [],
            clients: [],
            currency: 'PLN',
            organizationBillableRate: null,
            canCreateProject: false,
            enableEstimatedTime: false,
        },
        global: {
            provide: {
                organization: computed(() => ({ interval_format: 'hours-minutes' })),
            },
            stubs: {
                DialogModal: {
                    template: '<div><slot name="content" /><slot name="footer" /></div>',
                },
                TimeTrackerProjectTaskDropdown: true,
                TagDropdown: true,
                Select: true,
                DatePicker: true,
            },
        },
    });
    return { wrapper, createTimeEntry };
}

describe('TimeEntryCreateModal', () => {
    it.each([false, true])(
        'keeps yesterday at 09:00 and calculates 17:00 from 8h (end date selected: %s)',
        async (selectEndDate) => {
            const { wrapper, createTimeEntry } = mountModal();
            const datePickers = wrapper.findAllComponents(DatePicker);
            assert(datePickers[0] && datePickers[1]);
            datePickers[0].vm.$emit('update:modelValue', '2026-10-25T08:00:00+01:00');
            await nextTick();

            const [startInput, endInput] = wrapper.findAll<HTMLInputElement>(
                '[data-testid="time_picker_input"]'
            );
            assert(startInput && endInput);
            await startInput.setValue('09:00');
            await startInput.trigger('blur');

            if (selectEndDate) {
                datePickers[1].vm.$emit('update:modelValue', '2026-10-25T09:00:00+01:00');
                await nextTick();
            }

            const duration = wrapper.get<HTMLInputElement>('input[name="Duration"]');
            await duration.setValue('8h');
            await duration.trigger('blur');

            expect(startInput.element.value).toBe('09:00');
            expect(endInput.element.value).toBe('17:00');
            expect(duration.element.value).toBe('8h 00min');

            await wrapper.getComponent({ name: 'PrimaryButton' }).trigger('click');
            expect(createTimeEntry).toHaveBeenCalledWith(
                expect.objectContaining({
                    start: '2026-10-25T08:00:00Z',
                    end: '2026-10-25T16:00:00Z',
                })
            );
        }
    );
});
