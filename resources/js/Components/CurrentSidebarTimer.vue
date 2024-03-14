<script setup lang="ts">
import TimeTrackerStartStop from '@/Components/common/TimeTrackerStartStop.vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import dayjs from 'dayjs';
import { formatHumanReadableDuration } from '@/utils/time';
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
</script>

<template>
    <div class="py-4 px-2 flex justify-between items-center">
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
