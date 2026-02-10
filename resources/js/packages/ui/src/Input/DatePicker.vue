<script setup lang="ts">
import { computed, inject, ref, type ComputedRef } from 'vue';
import {
    getLocalizedDayJs,
    formatDate,
    firstDayIndex,
    type WeekStartDay,
} from '@/packages/ui/src/utils/time';
import { Popover, PopoverContent, PopoverTrigger } from '@/packages/ui/src/popover';
import { Calendar } from '@/Components/ui/calendar';
import { Button } from '@/packages/ui/src/Buttons';
import { CalendarIcon } from 'lucide-vue-next';
import { parseDate, type DateValue } from '@internationalized/date';
import type { Organization } from '@/packages/api/src';

const props = defineProps<{
    tabindex?: string;
    class?: string;
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
</script>

<template>
    <div class="w-full">
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <Button
                    variant="input"
                    size="sm"
                    :tabindex="tabindex"
                    :class="['w-full px-2 gap-1.5', props.class]">
                    <CalendarIcon class="!size-3 text-muted-foreground" />
                    <span>{{ displayDate || 'Pick a date' }}</span>
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
