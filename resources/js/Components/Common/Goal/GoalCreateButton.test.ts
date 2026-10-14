import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import GoalCreateButton from './GoalCreateButton.vue';

function mountGoalCreateButton(canCreateMoreGoals: boolean) {
    return mount(GoalCreateButton, {
        props: { canCreateMoreGoals },
        slots: { default: 'Create your first goal' },
        global: {
            stubs: {
                GoalCreateModal: { name: 'GoalCreateModal', props: ['show'], template: '<div />' },
                UpgradeModal: {
                    name: 'UpgradeModal',
                    props: ['show', 'feature'],
                    template: '<div />',
                },
            },
        },
    });
}

describe('GoalCreateButton', () => {
    it('opens the create modal while more goals can be created', async () => {
        const wrapper = mountGoalCreateButton(true);

        await wrapper.get('button').trigger('click');

        expect(wrapper.getComponent({ name: 'GoalCreateModal' }).props('show')).toBe(true);
        expect(wrapper.getComponent({ name: 'UpgradeModal' }).props('show')).toBe(false);
    });

    it('opens the upgrade modal once the goal limit is reached', async () => {
        const wrapper = mountGoalCreateButton(false);

        expect(wrapper.get('button').text()).toBe('Create your first goal');
        await wrapper.get('button').trigger('click');

        const upgradeModal = wrapper.getComponent({ name: 'UpgradeModal' });
        expect(upgradeModal.props('show')).toBe(true);
        expect(upgradeModal.props('feature')).toBe('goals');
        expect(wrapper.getComponent({ name: 'GoalCreateModal' }).props('show')).toBe(false);
    });
});
