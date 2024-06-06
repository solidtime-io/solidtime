<script setup lang="ts">
import SelectDropdown from '@/Components/Common/SelectDropdown.vue';
import type { BillableKey } from '@/utils/useProjects';
import Badge from '@/Components/Common/Badge.vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';

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

function getKeyFromItem(item: Option) {
    return item.key;
}

function getNameFromItem(item: Option) {
    return item.name;
}

function getNameForKey(key: BillableKey | undefined) {
    const item = options.find((item) => getKeyFromItem(item) === key);
    if (item) {
        return getNameFromItem(item);
    }
    return '';
}
</script>

<template>
    <SelectDropdown
        v-model="model"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameFromItem"
        :items="options">
        <template #trigger>
            <Badge size="xlarge" class="bg-input-background cursor-pointer">
                <span>
                    {{ getNameForKey(model) }}
                </span>
                <ChevronDownIcon class="text-muted w-5"></ChevronDownIcon>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
