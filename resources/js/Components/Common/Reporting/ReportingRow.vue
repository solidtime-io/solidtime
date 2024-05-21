<script setup lang="ts">
import { formatHumanReadableDuration } from '@/utils/time';
import { formatCents } from '@/utils/money';
import GroupedItemsCountButton from '@/Components/Common/GroupedItemsCountButton.vue';
import { ref } from 'vue';
import { twMerge } from 'tailwind-merge';
import { useReportingStore } from '@/utils/useReporting';
const { getNameForReportingRowEntry } = useReportingStore();

type AggregatedGroupedData = GroupedData & {
    grouped_type?: string | null;
    grouped_data?: GroupedData[] | null;
};

type GroupedData = {
    key: string | null;
    seconds: number;
    cost: number;
};

const props = defineProps<{
    entry: AggregatedGroupedData;
    indent?: boolean;
    type: string | null;
}>();

function getNameForKey(key: string | null) {
    return getNameForReportingRowEntry(key, props.type);
}
const expanded = ref(false);
</script>

<template>
    <div
        class="contents text-white [&>*]:transition [&>*]:border-card-background-separator [&>*]:border-b [&>*]:h-[50px]">
        <div
            :class="
                twMerge(
                    'pl-6 font-medium flex items-center space-x-3',
                    props.indent ? 'pl-16' : ''
                )
            ">
            <GroupedItemsCountButton
                :expanded="expanded"
                @click="expanded = !expanded"
                v-if="entry.grouped_data && entry.grouped_data?.length > 0">
                {{ entry.grouped_data?.length }}
            </GroupedItemsCountButton>
            <span>
                {{ getNameForKey(entry.key) }}
            </span>
        </div>
        <div class="justify-end flex items-center">
            {{ formatHumanReadableDuration(entry.seconds) }}
        </div>
        <div class="justify-end pr-6 flex items-center">
            {{ formatCents(entry.cost) }}
        </div>
    </div>
    <div
        class="col-span-3 grid bg-quaternary"
        style="grid-template-columns: 1fr 150px 150px"
        v-if="expanded && entry.grouped_data">
        <ReportingRow
            indent
            v-for="subEntry in entry.grouped_data"
            :type="entry?.grouped_type ?? null"
            :key="subEntry.key ?? 'none'"
            :entry="subEntry"></ReportingRow>
    </div>
</template>

<style scoped></style>
