<script setup lang="ts">
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/ui/popover';
import { Button } from '@/Components/ui/button';
import { Calendar } from '@/Components/ui/calendar';
import { CalendarIcon } from 'lucide-vue-next';
import { formatDateLocalized } from '@/packages/ui/src/utils/time';
import { parseDate, type DateValue } from '@internationalized/date';
import { computed, inject, type ComputedRef } from 'vue';
import { type Organization } from '@/packages/api/src';
import { getLocalizedDayJs } from '@/packages/ui/src/utils/time';

const props = defineProps<{
    class?: string;
    tabindex?: string;
}>();

const model = defineModel<string | null>();
const emit = defineEmits<{
    changed: [string];
}>();

const handleChange = (date: DateValue | undefined) => {
    if (!date) {
        model.value = null;
        return;
    }

    const dayjs = getLocalizedDayJs(model.value);
    model.value = dayjs
        .year(date.year)
        .month(date.month - 1) // CalendarDate uses 1-based months
        .date(date.day)
        .format();
    emit('changed', model.value);
};

const date = computed(() => {
    return model.value
        ? parseDate(getLocalizedDayJs(model.value).format('YYYY-MM-DD'))
        : undefined;
});

const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="input"
                size="sm"
                :class="[
                    'w-full gap-1.5 justify-center text-left font-normal',
                    !model && 'text-muted-foreground',
                    props.class,
                ]"
                :tabindex="tabindex">
                <CalendarIcon class="h-3 w-3" />
                <span class="text-center">
                    {{
                        model
                            ? formatDateLocalized(
                                  model,
                                  organization?.date_format
                              )
                            : 'Pick a date'
                    }}
                </span>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar
                mode="single"
                :model-value="date"
                :initial-focus="true"
                @update:model-value="handleChange"
                />
        </PopoverContent>
    </Popover>
</template>
