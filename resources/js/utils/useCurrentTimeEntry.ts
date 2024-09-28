import { defineStore } from 'pinia';
import { computed, reactive, ref } from 'vue';
import { api } from '@/packages/api/src';
import type { TimeEntry } from '@/packages/api/src';
import dayjs, { Dayjs } from 'dayjs';
import utc from 'dayjs/plugin/utc';
import {
    getCurrentMembershipId,
    getCurrentOrganizationId,
    getCurrentUserId,
} from '@/utils/useUser';
import { useLocalStorage } from '@vueuse/core';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { useNotificationsStore } from '@/utils/notification';

dayjs.extend(utc);

const emptyTimeEntry = {
    id: '',
    description: null,
    user_id: '',
    start: '',
    end: null,
    duration: null,
    task_id: null,
    project_id: null,
    tags: [],
    billable: false,
    organization_id: '',
} as TimeEntry;

export const useCurrentTimeEntryStore = defineStore('currentTimeEntry', () => {
    const currentTimeEntry = ref<TimeEntry>(reactive(emptyTimeEntry));
    const { handleApiRequestNotifications } = useNotificationsStore();

    useLocalStorage('solidtime/current-time-entry', currentTimeEntry, {
        deep: true,
    });

    function $reset() {
        currentTimeEntry.value = { ...emptyTimeEntry };
    }

    const now = ref<null | Dayjs>(null);
    const interval = ref<ReturnType<typeof setInterval> | null>(null);

    function startLiveTimer() {
        stopLiveTimer();
        now.value = dayjs().utc();
        interval.value = setInterval(() => {
            now.value = dayjs().utc();
        }, 1000);
    }

    function stopLiveTimer() {
        if (interval.value !== null) {
            clearInterval(interval.value);
        }
    }

    async function fetchCurrentTimeEntry() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            try {
                const timeEntriesResponse = await api.getMyActiveTimeEntry({});
                if (timeEntriesResponse?.data) {
                    if (timeEntriesResponse.data) {
                        currentTimeEntry.value = timeEntriesResponse.data;
                    } else {
                        currentTimeEntry.value = { ...emptyTimeEntry };
                    }
                }
            } catch (error) {
                currentTimeEntry.value = { ...emptyTimeEntry };
            }
        } else {
            throw new Error(
                'Failed to fetch current time entry because organization ID is missing.'
            );
        }
    }

    async function startTimer() {
        const organization = getCurrentOrganizationId();
        const membership = getCurrentMembershipId();
        if (organization && membership) {
            const startTime =
                currentTimeEntry.value.start !== ''
                    ? currentTimeEntry.value.start
                    : dayjs().utc().format();
            const response = await handleApiRequestNotifications(
                () =>
                    api.createTimeEntry(
                        {
                            member_id: membership,
                            start: startTime,
                            description: currentTimeEntry.value?.description,
                            project_id: currentTimeEntry.value?.project_id,
                            task_id: currentTimeEntry.value?.task_id,
                            billable: currentTimeEntry.value.billable,
                            tags: currentTimeEntry.value?.tags,
                        },
                        { params: { organization: organization } }
                    ),
                'Timer started!'
            );
            if (response?.data) {
                currentTimeEntry.value = response.data;
            }
        } else {
            throw new Error(
                'Failed to fetch current time entry because organization ID is missing.'
            );
        }
    }

    async function stopTimer() {
        const user = getCurrentUserId();
        const organization = getCurrentOrganizationId();
        if (organization) {
            const currentDateTime = dayjs().utc().format();
            await handleApiRequestNotifications(
                () =>
                    api.updateTimeEntry(
                        {
                            user_id: user,
                            start: currentTimeEntry.value.start,
                            end: currentDateTime,
                        },
                        {
                            params: {
                                organization: organization,
                                timeEntry: currentTimeEntry.value.id,
                            },
                        }
                    ),
                'Timer stopped!'
            );
            $reset();
        } else {
            throw new Error(
                'Failed to stop current timer because organization ID is missing.'
            );
        }
    }

    async function updateTimer() {
        const user = getCurrentUserId();
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.updateTimeEntry(
                        {
                            description: currentTimeEntry.value.description,
                            user_id: user,
                            project_id: currentTimeEntry.value.project_id,
                            task_id: currentTimeEntry.value.task_id,
                            start: currentTimeEntry.value.start,
                            billable: currentTimeEntry.value.billable,
                            end: null,
                            tags: currentTimeEntry.value.tags,
                        },
                        {
                            params: {
                                organization: organization,
                                timeEntry: currentTimeEntry.value.id,
                            },
                        }
                    ),
                'Time entry updated!'
            );
            if (response?.data) {
                currentTimeEntry.value = response.data;
            }
        } else {
            throw new Error(
                'Failed to fetch current time entry because organization ID is missing.'
            );
        }
    }

    const isActive = computed(() => {
        if (currentTimeEntry.value) {
            return (
                currentTimeEntry.value.start !== '' &&
                currentTimeEntry.value.start !== null &&
                currentTimeEntry.value.end === null
            );
        }
        return false;
    });

    async function setActiveState(newState: boolean) {
        if (newState) {
            startLiveTimer();
            await startTimer();
        } else {
            stopLiveTimer();
            await stopTimer();
        }
        useTimeEntriesStore().fetchTimeEntries();
    }

    return {
        currentTimeEntry,
        fetchCurrentTimeEntry,
        updateTimer,
        isActive,
        startLiveTimer,
        stopLiveTimer,
        now,
        setActiveState,
    };
});
