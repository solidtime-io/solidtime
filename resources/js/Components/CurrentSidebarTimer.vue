<script setup lang="ts">
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import dayjs from 'dayjs';
import { formatHumanReadableDuration } from '@/utils/time';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
const store = useCurrentTimeEntryStore();
const { currentTimeEntry, now, isActive } = storeToRefs(store);
const { onToggleButtonPress } = store;

const currentTime = computed(() => {
    if (now.value && currentTimeEntry.value.start) {
        const startTime = dayjs(currentTimeEntry.value.start);
        const diff = now.value.diff(startTime, 's');
        // return dayjs(diff).utc().format('HH:mm:ss');
        return formatHumanReadableDuration(diff);
    }
    return formatHumanReadableDuration(0);
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
    <div class="py-4 px-2 flex justify-between items-center relative">
        <div
            class="absolute w-full h-full backdrop-blur-sm z-10 flex items-center justify-center"
            v-if="isRunningInDifferentOrganization">
            <div
                class="w-full h-[calc(100%+10px)] absolute bg-default-background opacity-75 backdrop-blur-sm"></div>
            <div class="flex space-x-3 items-center w-full z-20 justify-center">
                <span class="text-xs text-center text-white">
                    The Timer is running in a different organization.
                </span>
            </div>
        </div>
        <div>
            <div class="text-muted font-extrabold text-xs">Current Timer</div>
            <div class="text-white font-medium text-lg py-1">
                {{ currentTime }}
            </div>
        </div>
        <TimeTrackerStartStop
            :active="isActive"
            @changed="onToggleButtonPress"
            size="base"></TimeTrackerStartStop>
    </div>
</template>
