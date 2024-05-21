<script setup lang="ts">
import { FolderIcon } from '@heroicons/vue/16/solid';
import SelectDropdown from '@/Components/Common/SelectDropdown.vue';
import Badge from '@/Components/Common/Badge.vue';
import { computed } from 'vue';
import { CheckCircleIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import BillableIcon from '@/Components/Common/Icons/BillableIcon.vue';

const groupByOptions = [
    {
        label: 'Members',
        value: 'user',
        icon: UserGroupIcon,
    },
    {
        label: 'Projects',
        value: 'project',
        icon: FolderIcon,
    },
    {
        label: 'Tasks',
        value: 'task',
        icon: CheckCircleIcon,
    },
    {
        label: 'Billable',
        value: 'billable',
        icon: BillableIcon,
    },
];

const model = defineModel<string | null>({ default: null });

const icon = computed(() => {
    return groupByOptions.find((option) => option.value === model.value)?.icon;
});
const title = computed(() => {
    return groupByOptions.find((option) => option.value === model.value)?.label;
});
</script>

<template>
    <SelectDropdown
        v-model="model"
        :get-key-from-item="(item) => item.value"
        :get-name-for-item="(item) => item.label"
        :items="groupByOptions">
        <template v-slot:trigger>
            <Badge
                size="large"
                class="cursor-pointer hover:bg-card-background transition space-x-5 flex">
                <component :is="icon" class="h-4 text-muted"></component>
                <span> {{ title }} </span>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
