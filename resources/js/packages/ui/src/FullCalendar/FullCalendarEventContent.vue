<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import { formatHumanReadableDuration, getDayJsInstance } from '../utils/time';
import type { Organization } from '@/packages/api/src';
import { Coffee } from '@lucide/vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/20/solid';

const props = defineProps<{
    title: string;
    projectName?: string | null;
    taskName?: string | null;
    clientName?: string | null;
    durationSeconds?: number;
    start?: string | Date | null;
    end?: string | Date | null;
    isBreak?: boolean;
    isMisplacedBreak?: boolean;
}>();

const effectiveDurationSeconds = computed(() => {
    if (typeof props.durationSeconds === 'number') {
        return props.durationSeconds;
    }
    if (props.start && props.end) {
        const end = getDayJsInstance()(props.end as unknown as string | Date);
        const start = getDayJsInstance()(props.start as unknown as string | Date);
        const minutes = end.diff(start, 'minutes');
        return minutes * 60;
    }
    return 0;
});

const organization = inject('organization') as ComputedRef<Organization | undefined> | undefined;
const intervalFormat = computed(() => organization?.value?.interval_format);
const numberFormat = computed(() => organization?.value?.number_format);

const formattedDuration = computed(() =>
    formatHumanReadableDuration(
        effectiveDurationSeconds.value,
        intervalFormat.value,
        numberFormat.value
    )
);
</script>

<template>
    <div class="text-2xs leading-tight px-0.5 py-1">
        <div class="font-semibold flex items-center gap-1">
            <Coffee v-if="isBreak" class="w-3 h-3 shrink-0" />
            <span class="truncate">{{ title }}</span>
            <ExclamationTriangleIcon
                v-if="isMisplacedBreak"
                data-testid="calendar_break_placement_hint"
                title="This break does not align with your work entries"
                class="w-3 h-3 shrink-0 text-amber-600 dark:text-amber-400" />
        </div>
        <div v-if="projectName" class="font-medium opacity-90">
            {{ projectName }}
        </div>
        <div v-if="taskName" class="font-medium">
            {{ taskName }}
        </div>
        <div v-if="clientName" class="opacity-85">
            {{ clientName }}
        </div>
        <div class="opacity-90" data-duration>
            {{ formattedDuration }}
        </div>
    </div>
</template>
