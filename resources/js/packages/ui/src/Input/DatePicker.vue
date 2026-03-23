<script setup lang="ts">
import { computed, inject, ref, type ComputedRef } from 'vue';
import {
    getLocalizedDayJs,
    formatDate,
    firstDayIndex,
    type WeekStartDay,
} from '@/packages/ui/src/utils/time';
import { Popover, PopoverContent, PopoverTrigger } from '@/packages/ui/src/popover';
import { Calendar } from '..';
import { Button } from '@/packages/ui/src/Buttons';
import { CalendarIcon, XIcon } from 'lucide-vue-next';
import { parseDate, type DateValue } from '@internationalized/date';
import type { Organization } from '@/packages/api/src';

const props = defineProps<{
    tabindex?: string;
    class?: string;
    size?: 'sm' | 'default';
    clearable?: boolean;
}>();

// This has to be a localized timestamp, not UTC
const model = defineModel<string | null>({
    default: null,
});

const emit = defineEmits(['changed']);

const open = ref(false);

const organization = inject<ComputedRef<Organization>>('organization');

const weekStartsOn = computed((): WeekStartDay => firstDayIndex.value as WeekStartDay);

const dateString = computed(() => {
    if (!model.value) return null;
    return getLocalizedDayJs(model.value).format('YYYY-MM-DD');
});

const calendarDate = computed(() => {
    if (!dateString.value) return undefined;
    return parseDate(dateString.value);
});

const displayDate = computed(() => {
    if (!dateString.value) return '';
    return formatDate(dateString.value, organization?.value?.date_format);
});

function handleDateSelect(newDate: DateValue | undefined) {
    if (!newDate) return;
    // If model.value is null, start from current date/time
    const baseDate = model.value ? getLocalizedDayJs(model.value) : getLocalizedDayJs();
    const newValue = baseDate
        .set('year', newDate.year)
        .set('month', newDate.month - 1) // CalendarDate months are 1-indexed, dayjs is 0-indexed
        .set('date', newDate.day)
        .format();
    model.value = newValue;
    emit('changed', newValue);
    open.value = false;
}

function handleClear(event: Event) {
    event.stopPropagation();
    model.value = null;
    emit('changed', null);
}
</script>

<template>
    <div class="w-full">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    variant="input"
                    :size="props.size ?? 'default'"
                    :tabindex="tabindex"
                    :class="['w-full px-2 gap-1.5', props.class]">
                    <CalendarIcon class="!size-3 text-muted-foreground" />
                    <span :class="{ 'flex-1': clearable }">{{
                        displayDate || 'Pick a date'
                    }}</span>
                    <span
                        v-if="clearable && model"
                        role="button"
                        class="hover:bg-muted rounded p-0.5 transition-colors"
                        @click.stop="handleClear($event)">
                        <XIcon class="size-3" />
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="center">
                <Calendar
                    mode="single"
                    :model-value="calendarDate"
                    :week-starts-on="weekStartsOn"
                    @update:model-value="handleDateSelect" />
            </PopoverContent>
        </Popover>
    </div>
</template>
