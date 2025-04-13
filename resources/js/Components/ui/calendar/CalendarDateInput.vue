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
import { parseDate } from '@internationalized/date';
import { computed } from 'vue';

const model = defineModel<string | null>();
const emit = defineEmits<{
    blur: [];
}>();

const handleChange = (date: string) => {
    model.value = date;
};

const handleBlur = () => {
    emit('blur');
};

const date = computed(() => {
    return model.value ? parseDate(model.value) : undefined;
});
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="input"
                size="input"
                :class="[
                    'w-full justify-start text-left font-normal',
                    !model && 'text-muted-foreground'
                ]"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ model ? formatDateLocalized(model) : 'Pick a date' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <Calendar
                mode="single"
                :model-value="date"
                :initial-focus="true"
                @update:model-value="(date) => handleChange(date ? date.toString() : '')"
                @blur="handleBlur"
            />
        </PopoverContent>
    </Popover>
</template> 