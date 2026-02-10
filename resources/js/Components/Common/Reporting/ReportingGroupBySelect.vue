<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { type Component, computed } from 'vue';

const model = defineModel<string | null>({ default: null });
const props = defineProps<{
    groupByOptions: { value: string; label: string; icon: Component }[];
}>();
const emit = defineEmits<{
    changed: [];
}>();
const icon = computed(() => {
    return props.groupByOptions.find((option) => option.value === model.value)?.icon;
});
const title = computed(() => {
    return props.groupByOptions.find((option) => option.value === model.value)?.label;
});
</script>

<template>
    <Select v-model="model" @update:model-value="emit('changed')">
        <SelectTrigger size="sm" :show-chevron="false">
            <SelectValue class="flex items-center gap-2">
                <component :is="icon" class="h-4 text-icon-default" />
                <span>{{ title }}</span>
            </SelectValue>
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-for="option in groupByOptions" :key="option.value" :value="option.value">
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>

<style scoped></style>
