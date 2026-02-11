<script setup lang="ts">
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import GroupedItemsCountButton from '@/packages/ui/src/GroupedItemsCountButton.vue';
import { ref, inject, type ComputedRef } from 'vue';
import { twMerge } from 'tailwind-merge';
import type { Organization } from '@/packages/api/src';

type AggregatedGroupedData = GroupedData & {
    grouped_data?: GroupedData[] | null;
};

type GroupedData = {
    seconds: number;
    cost: number | null;
    description: string | null | undefined;
};

const props = defineProps<{
    entry: AggregatedGroupedData;
    indent?: boolean;
    currency: string;
    showCost?: boolean;
}>();

const expanded = ref(false);

const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <div
        class="contents text-text-primary [&>*]:transition [&>*]:border-card-background-separator [&>*]:border-b [&>*]:h-[50px]">
        <div :class="twMerge('pl-6 flex items-center space-x-3', props.indent ? 'pl-16' : '')">
            <GroupedItemsCountButton
                v-if="entry.grouped_data && entry.grouped_data?.length > 0"
                :expanded="expanded"
                @click="expanded = !expanded">
                {{ entry.grouped_data?.length }}
            </GroupedItemsCountButton>
            <span>
                {{ entry.description }}
            </span>
        </div>
        <div class="justify-end flex items-center" :class="!showCost ? 'pr-6' : ''">
            {{
                formatHumanReadableDuration(
                    entry.seconds,
                    organization?.interval_format,
                    organization?.number_format
                )
            }}
        </div>
        <div v-if="showCost" class="justify-end pr-6 flex items-center">
            {{
                entry.cost
                    ? formatCents(
                          entry.cost,
                          props.currency,
                          organization?.currency_format,
                          organization?.currency_symbol,
                          organization?.number_format
                      )
                    : '--'
            }}
        </div>
    </div>
    <div
        v-if="expanded && entry.grouped_data"
        :class="showCost ? 'col-span-3' : 'col-span-2'"
        class="grid bg-tertiary"
        :style="`grid-template-columns: 1fr 150px ${showCost ? '150px' : ''}`">
        <ReportingRow
            v-for="subEntry in entry.grouped_data"
            :key="subEntry.description ?? 'none'"
            :currency="props.currency"
            :show-cost="showCost"
            indent
            :entry="subEntry"></ReportingRow>
    </div>
</template>

<style scoped></style>
