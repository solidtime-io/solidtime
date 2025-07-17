<script setup lang="ts">
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import dayjs from 'dayjs';
import { formatDuration } from '@/packages/ui/src/utils/time';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';

const store = useCurrentTimeEntryStore();
const { currentTimeEntry, now, isActive } = storeToRefs(store);
const { setActiveState } = store;

const currentTime = computed(() => {
    if (now.value && currentTimeEntry.value.start) {
        const startTime = dayjs(currentTimeEntry.value.start);
        const diff = now.value.diff(startTime, 's');
        return formatDuration(diff);
    }
    return formatDuration(0);
});

const isRunningInDifferentOrganization = computed(() => {
    return (
        currentTimeEntry.value.organization_id &&
        getCurrentOrganizationId() &&
        currentTimeEntry.value.organization_id !== getCurrentOrganizationId()
    );
});
</script>

<template>
    <div class="pt-3 pb-2.5 px-2 flex justify-between items-center relative">
        <div
            v-if="isRunningInDifferentOrganization"
            class="absolute w-full h-full backdrop-blur-sm z-10 flex items-center justify-center">
            <div
                class="w-full h-[calc(100%+10px)] absolute bg-default-background opacity-75 backdrop-blur-sm"></div>
            <div class="flex space-x-3 items-center w-full z-20 justify-center">
                <span class="text-xs text-center text-text-primary">
                    The Timer is running in a different organization.
                </span>
            </div>
        </div>
        <div>
            <div class="text-text-secondary font-medium text-xs">
                Current Timer
            </div>
            <div class="text-text-primary font-medium text-lg">
                {{ currentTime }}
            </div>
        </div>
        <TimeTrackerStartStop
            :active="isActive"
            size="base"
            @changed="setActiveState"></TimeTrackerStartStop>
    </div>
</template>
