<script setup lang="ts">
import { Popover, PopoverContent, PopoverTrigger, Button } from '..';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Field, FieldLabel } from '../field';
import { Settings } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import type { CalendarSettings } from './calendarSettings';

export type { CalendarSettings };

const props = defineProps<{
    settings: CalendarSettings;
}>();

const emit = defineEmits<{
    'update:settings': [value: CalendarSettings];
}>();

const snapMinutes = ref(String(props.settings.snapMinutes));
const startHour = ref(String(props.settings.startHour));
const endHour = ref(String(props.settings.endHour));
const slotMinutes = ref(String(props.settings.slotMinutes));

watch(
    () => props.settings,
    (s) => {
        snapMinutes.value = String(s.snapMinutes);
        startHour.value = String(s.startHour);
        endHour.value = String(s.endHour);
        slotMinutes.value = String(s.slotMinutes);
    }
);

function emitUpdate(partial: Partial<CalendarSettings>) {
    emit('update:settings', { ...props.settings, ...partial });
}

function onSnapChange(value: string) {
    snapMinutes.value = value;
    emitUpdate({ snapMinutes: parseInt(value) });
}

function onStartHourChange(value: string) {
    const newStart = parseInt(value);
    // Ensure start < end
    if (newStart >= parseInt(endHour.value)) {
        startHour.value = String(props.settings.startHour);
        return;
    }
    startHour.value = value;
    emitUpdate({ startHour: newStart });
}

function onEndHourChange(value: string) {
    const newEnd = parseInt(value);
    // Ensure end > start
    if (newEnd <= parseInt(startHour.value)) {
        endHour.value = String(props.settings.endHour);
        return;
    }
    endHour.value = value;
    emitUpdate({ endHour: newEnd });
}

function onSlotChange(value: string) {
    slotMinutes.value = value;
    emitUpdate({ slotMinutes: parseInt(value) });
}

const snapOptions = [
    { value: '1', label: '1 min' },
    { value: '5', label: '5 min' },
    { value: '10', label: '10 min' },
    { value: '15', label: '15 min' },
    { value: '30', label: '30 min' },
    { value: '60', label: '1 hour' },
];

const slotOptions = [
    { value: '5', label: '5 min' },
    { value: '10', label: '10 min' },
    { value: '15', label: '15 min' },
    { value: '30', label: '30 min' },
    { value: '60', label: '1 hour' },
];

// Generate hour options 0-24
const hourOptions = Array.from({ length: 25 }, (_, i) => ({
    value: String(i),
    label:
        i === 0
            ? '12:00 AM'
            : i === 12
              ? '12:00 PM'
              : i === 24
                ? '12:00 AM (next)'
                : i < 12
                  ? `${i}:00 AM`
                  : `${i - 12}:00 PM`,
}));
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" aria-label="Calendar settings" class="h-8 w-8 p-0">
                <Settings class="h-4 w-4 text-muted-foreground" />
            </Button>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-72 p-4">
            <div class="space-y-4">
                <div class="text-sm font-semibold">Calendar Settings</div>

                <Field>
                    <FieldLabel for="calendar-snap">Snap Interval</FieldLabel>
                    <Select
                        :model-value="snapMinutes"
                        @update:model-value="(v) => onSnapChange(v as string)">
                        <SelectTrigger id="calendar-snap" size="sm" class="w-full">
                            <SelectValue placeholder="Snap interval" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in snapOptions"
                                :key="opt.value"
                                :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Field>

                <Field>
                    <FieldLabel for="calendar-start-hour">Start Time</FieldLabel>
                    <Select
                        :model-value="startHour"
                        @update:model-value="(v) => onStartHourChange(v as string)">
                        <SelectTrigger id="calendar-start-hour" size="sm" class="w-full">
                            <SelectValue placeholder="Start time" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in hourOptions.slice(0, -1)"
                                :key="opt.value"
                                :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Field>

                <Field>
                    <FieldLabel for="calendar-end-hour">End Time</FieldLabel>
                    <Select
                        :model-value="endHour"
                        @update:model-value="(v) => onEndHourChange(v as string)">
                        <SelectTrigger id="calendar-end-hour" size="sm" class="w-full">
                            <SelectValue placeholder="End time" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in hourOptions.slice(1)"
                                :key="opt.value"
                                :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Field>

                <Field>
                    <FieldLabel for="calendar-scale">Grid Scale</FieldLabel>
                    <Select
                        :model-value="slotMinutes"
                        @update:model-value="(v) => onSlotChange(v as string)">
                        <SelectTrigger id="calendar-scale" size="sm" class="w-full">
                            <SelectValue placeholder="Grid scale" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in slotOptions"
                                :key="opt.value"
                                :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
            </div>
        </PopoverContent>
    </Popover>
</template>
