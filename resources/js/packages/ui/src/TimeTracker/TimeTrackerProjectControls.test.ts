import { shallowMount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import TimeTrackerProjectControls from './TimeTrackerProjectControls.vue';
import TimeTrackerProjectTaskDropdown from './TimeTrackerProjectTaskDropdown.vue';
import type { Project, TimeEntry } from '@/packages/api/src';

function timeEntry(overrides: Partial<TimeEntry> = {}): TimeEntry {
    return {
        id: 'te-1',
        description: '',
        start: '2026-07-14T09:00:00Z',
        end: null,
        duration: null,
        project_id: null,
        task_id: null,
        organization_id: 'org-1',
        user_id: 'user-1',
        tags: [],
        billable: false,
        type: 'work',
        ...overrides,
    } as TimeEntry;
}

describe('TimeTrackerProjectControls', () => {
    it('adopts the billable default of a newly selected project', async () => {
        const current = timeEntry({ project_id: null, billable: false });
        const billableProject = { id: 'p-1', is_billable: true } as Project;
        const wrapper = shallowMount(TimeTrackerProjectControls, {
            props: {
                currentTimeEntry: current,
                projects: [billableProject],
                tasks: [],
                tags: [],
                clients: [],
                createTag: vi.fn(),
                createProject: vi.fn(),
                createClient: vi.fn(),
                currency: 'EUR',
                organizationBillableRate: null,
                enableEstimatedTime: false,
                canCreateProject: false,
            },
        });

        const dropdown = wrapper.findComponent(TimeTrackerProjectTaskDropdown);
        // The dropdown sets the project via v-model, then emits `changed`.
        dropdown.vm.$emit('update:project', 'p-1');
        dropdown.vm.$emit('changed');
        await nextTick();

        expect(current.billable).toBe(true);
        expect(wrapper.emitted('updateTimeEntry')).toBeTruthy();
    });
});
