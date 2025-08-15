<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import { formatHumanReadableDuration, getDayJsInstance } from '../utils/time';
import type { Organization } from '@/packages/api/src';

const props = defineProps<{
    title: string;
    projectName?: string | null;
    taskName?: string | null;
    clientName?: string | null;
    durationSeconds?: number;
    start?: string | Date | null;
    end?: string | Date | null;
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
    <div class="text-xs leading-tight">
        <div class="font-semibold mb-0.5">{{ title }}</div>
        <div v-if="projectName" class="font-medium text-[0.6875rem] opacity-90">
            {{ projectName }}
        </div>
        <div v-if="taskName" class="font-medium text-[0.6875rem] opacity-90">
            {{ taskName }}
        </div>
        <div v-if="clientName" class="text-[0.625rem] italic opacity-85">
            {{ clientName }}
        </div>
        <div class="text-[0.625rem] font-semibold opacity-90 mt-0.5">
            {{ formattedDuration }}
        </div>
    </div>
</template>
