<script setup lang="ts">
import { Popover, PopoverContent, PopoverTrigger } from '@/packages/ui/src';
import { Button } from '@/packages/ui/src';
import { Calendar } from '@/Components/ui/calendar';
import { CalendarIcon, XIcon } from 'lucide-vue-next';
import { formatDate } from '@/packages/ui/src/utils/time';
import { parseDate } from '@internationalized/date';
import { computed, inject, type ComputedRef } from 'vue';
import { type Organization } from '@/packages/api/src';

const model = defineModel<string | null>();
const emit = defineEmits<{
    blur: [];
}>();

defineProps<{
    clearable?: boolean;
}>();

const handleChange = (date: string) => {
    model.value = date;
};

const handleBlur = () => {
    emit('blur');
};

const handleClear = (event: Event) => {
    event.stopPropagation();
    model.value = null;
};

const date = computed(() => {
    return model.value ? parseDate(model.value) : undefined;
});

const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="input"
                size="input"
                :class="[
                    'w-full justify-start text-left font-normal',
                    !model && 'text-muted-foreground',
                ]">
                <CalendarIcon class="mr-2 h-4 w-4" />
                <span class="flex-1">
                    {{ model ? formatDate(model, organization?.date_format) : 'Pick a date' }}
                </span>
                <button
                    v-if="clearable && model"
                    class="ml-2 hover:bg-muted rounded p-1 transition-colors"
                    type="button"
                    @click="handleClear">
                    <XIcon class="h-4 w-4" />
                </button>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar
                mode="single"
                :model-value="date"
                :initial-focus="true"
                @update:model-value="(date) => handleChange(date ? date.toString() : '')"
                @blur="handleBlur" />
        </PopoverContent>
    </Popover>
</template>
