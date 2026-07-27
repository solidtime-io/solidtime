<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import {
    formatDate,
    formatHumanReadableDuration,
    formatWeekday,
} from '@/packages/ui/src/utils/time';
import Checkbox from '../Input/Checkbox.vue';
import { computed, inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';
import { CalendarIcon } from '@heroicons/vue/20/solid';

const organization = inject<ComputedRef<Organization>>('organization');

const props = withDefaults(
    defineProps<{
        date: string;
        duration: number;
        checked: boolean;
        breakDuration?: number;
    }>(),
    {
        breakDuration: 0,
    }
);

const hasBreak = computed(() => props.breakDuration > 0);

function formatDuration(seconds: number) {
    return formatHumanReadableDuration(
        seconds,
        organization?.value?.interval_format,
        organization?.value?.number_format
    );
}

// Without breaks the work time stands alone, so it needs no label. Once break
// time joins it, both halves are labelled to keep them apart. The separator and
// its spacing live inside the interpolated strings so the markup cannot collapse
// them away.
const workLabel = computed(() =>
    hasBreak.value ? `${formatDuration(props.duration)} work` : formatDuration(props.duration)
);
const breakLabel = computed(() => ` · ${formatDuration(props.breakDuration)} break`);

const emit = defineEmits<{
    selectAll: [];
    unselectAll: [];
}>();

function selectUnselectAll(value: boolean) {
    if (value) {
        emit('selectAll');
    } else {
        emit('unselectAll');
    }
}
</script>

<template>
    <div
        class="bg-background dark:bg-secondary border-b border-border-primary py-1 text-xs @sm:text-sm">
        <MainContainer>
            <div class="flex group justify-between items-center">
                <div class="flex items-center @lg:space-x-2 pl-1.5 @lg:pl-0">
                    <div class="w-5 hidden @lg:block">
                        <CalendarIcon
                            class="w-3 @sm:w-4 text-icon-default group-hover:hidden block">
                        </CalendarIcon>

                        <Checkbox
                            :checked="checked"
                            class="group-hover:block hidden"
                            @update:checked="selectUnselectAll"></Checkbox>
                    </div>
                    <span class="text-text-primary">
                        {{ formatWeekday(date) }}
                    </span>
                    <span class="text-text-secondary ml-2">
                        {{ formatDate(date, organization?.date_format) }}
                    </span>
                </div>
                <div class="flex items-center text-text-primary pr-2 @lg:pr-[92px]">
                    <span class="font-medium">{{ workLabel }}</span>
                    <span
                        v-if="hasBreak"
                        data-testid="day_break_duration"
                        class="text-text-secondary font-normal whitespace-pre"
                        >{{ breakLabel }}</span
                    >
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
