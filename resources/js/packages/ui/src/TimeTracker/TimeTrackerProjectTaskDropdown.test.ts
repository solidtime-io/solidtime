/* eslint-disable vue/one-component-per-file */
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, onMounted } from 'vue';
import TimeTrackerProjectTaskDropdown from './TimeTrackerProjectTaskDropdown.vue';
import type { Client, Project, Task } from '@/packages/api/src';

const DropdownStub = defineComponent({
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['update:modelValue'],
    setup(_, { emit, slots }) {
        onMounted(() => emit('update:modelValue', true));
        return () => h('div', [slots.trigger?.(), slots.content?.()]);
    },
});

const FocusTrapStub = defineComponent({
    setup(_, { slots }) {
        return () => h('div', slots.default?.());
    },
});

function mountDropdown(props: Record<string, unknown> = {}) {
    return mount(TimeTrackerProjectTaskDropdown, {
        props: {
            project: null,
            task: null,
            projects: [] as Project[],
            tasks: [] as Task[],
            clients: [] as Client[],
            createProject: vi.fn(),
            createClient: vi.fn(),
            currency: 'EUR',
            enableEstimatedTime: false,
            organizationBillableRate: null,
            canCreateProject: false,
            ...props,
        },
        global: {
            stubs: {
                Dropdown: DropdownStub,
                UseFocusTrap: FocusTrapStub,
            },
        },
    });
}

async function openDropdown() {
    const wrapper = mountDropdown();
    await nextTick();
    await nextTick();
    return wrapper;
}

describe('TimeTrackerProjectTaskDropdown', () => {
    it('keeps the existing empty-string no-project value by default', async () => {
        const wrapper = await openDropdown();

        await wrapper.find('[data-project-id=""]').trigger('click');

        expect(wrapper.emitted('update:project')?.at(-1)).toEqual(['']);
        expect(wrapper.emitted('changed')?.at(-1)).toEqual(['', null]);
    });

    it('can emit null for no-project consumers that use null as the domain value', async () => {
        const wrapper = mountDropdown({ project: 'p-1', noProjectValue: null });
        await nextTick();
        await nextTick();

        await wrapper.find('[data-project-id=""]').trigger('click');

        expect(wrapper.emitted('update:project')?.at(-1)).toEqual([null]);
        expect(wrapper.emitted('changed')?.at(-1)).toEqual([null, null]);
    });

    it('still exposes "No Project" when projects are empty and project creation is allowed', async () => {
        const wrapper = mountDropdown({ canCreateProject: true });
        await nextTick();
        await nextTick();

        await wrapper.find('[data-project-id=""]').trigger('click');

        expect(wrapper.emitted('changed')?.at(-1)).toEqual(['', null]);
    });
});
