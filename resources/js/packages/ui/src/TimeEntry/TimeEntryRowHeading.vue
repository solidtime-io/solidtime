<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import {
    formatDate,
    formatHumanReadableDuration,
    formatWeekday,
} from '@/packages/ui/src/utils/time';
import Checkbox from '../Input/Checkbox.vue';
import { inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';
import { CalendarIcon } from '@heroicons/vue/20/solid';

const organization = inject<ComputedRef<Organization>>('organization');

defineProps<{
    date: string;
    duration: number;
    checked: boolean;
}>();
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
                    <span class="font-medium text-text-secondary">
                        {{ formatWeekday(date) }}
                    </span>
                    <span class="text-text-tertiary ml-2">
                        {{ formatDate(date, organization?.date_format) }}
                    </span>
                </div>
                <div class="text-text-secondary pr-2 @lg:pr-[92px]">
                    <span class="font-medium">
                        {{
                            formatHumanReadableDuration(
                                duration,
                                organization?.interval_format,
                                organization?.number_format
                            )
                        }}
                    </span>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
