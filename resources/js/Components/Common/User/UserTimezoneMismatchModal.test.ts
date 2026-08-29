import { flushPromises, shallowMount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia } from 'pinia';
import { VueQueryPlugin } from '@tanstack/vue-query';
import UserTimezoneMismatchModal from './UserTimezoneMismatchModal.vue';
import TimezoneMismatchModal from '@/packages/ui/src/TimezoneMismatchModal.vue';
import { api } from '@/packages/api/src';

vi.mock('@/packages/api/src', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/packages/api/src')>()),
    api: {
        updateUser: vi.fn(),
    },
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
    usePage: () => ({
        props: {
            auth: {
                user: { id: 'user-1' },
            },
        },
    }),
}));

function mountModal() {
    return shallowMount(UserTimezoneMismatchModal, {
        props: { show: true },
        global: {
            plugins: [createPinia(), VueQueryPlugin],
        },
    });
}

describe('UserTimezoneMismatchModal', () => {
    beforeEach(() => {
        vi.mocked(api.updateUser).mockReset();
        Object.defineProperty(window.location, 'reload', {
            configurable: true,
            value: vi.fn(),
        });
    });

    it('saves the new timezone through the users API', async () => {
        vi.mocked(api.updateUser).mockResolvedValue({
            data: { id: 'user-1', timezone: 'Australia/Sydney' },
        } as never);

        const wrapper = mountModal();
        wrapper.findComponent(TimezoneMismatchModal).vm.$emit('update', 'Australia/Sydney');
        await flushPromises();

        expect(api.updateUser).toHaveBeenCalledWith(
            { timezone: 'Australia/Sydney' },
            { params: { user: 'user-1' } }
        );
        expect(wrapper.findComponent(TimezoneMismatchModal).props('show')).toBe(false);
        expect(window.location.reload).toHaveBeenCalled();
    });

    it('leaves the modal open when the update fails', async () => {
        vi.mocked(api.updateUser).mockRejectedValue(new Error('nope'));

        const wrapper = mountModal();
        wrapper.findComponent(TimezoneMismatchModal).vm.$emit('update', 'Australia/Sydney');
        await flushPromises();

        expect(wrapper.findComponent(TimezoneMismatchModal).props('show')).toBe(true);
        expect(window.location.reload).not.toHaveBeenCalled();
    });
});
