<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import type { BillableKey } from '@/types/projects';

const model = defineModel<BillableKey>({
    default: 'non-billable',
});

type Option = { key: BillableKey; name: string };

const options: Option[] = [
    {
        key: 'non-billable',
        name: 'Non-billable',
    },
    {
        key: 'default-rate',
        name: 'Default Rate',
    },
    {
        key: 'custom-rate',
        name: 'Custom Rate',
    },
];

function getNameForKey(key: BillableKey | undefined) {
    const item = options.find((item) => item.key === key);
    if (item) {
        return item.name;
    }
    return '';
}
</script>

<template>
    <Select v-model="model">
        <SelectTrigger>
            <SelectValue>{{ getNameForKey(model) }}</SelectValue>
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-for="option in options" :key="option.key" :value="option.key">
                {{ option.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>

<style scoped></style>
