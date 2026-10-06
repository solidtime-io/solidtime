<script setup lang="ts">
import UpgradeLockedBadge from '@/packages/ui/src/UpgradeLockedBadge.vue';
import { useBillableRatesLock } from '@/packages/ui/src/utils/useBillableRatesLock';
import TableHeading from '@/Components/Common/TableHeading.vue';

const { locked: billableRatesLocked } = useBillableRatesLock();
</script>

<template>
    <TableHeading>
        <div class="py-1.5 pr-3 text-left text-text-tertiary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            Name
        </div>
        <div class="px-3 py-1.5 text-left text-text-tertiary flex items-center gap-2">
            Billable Rate
            <UpgradeLockedBadge v-if="billableRatesLocked" />
        </div>
        <div class="px-3 py-1.5 text-left text-text-tertiary">Role</div>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <span class="sr-only">Edit</span>
        </div>
    </TableHeading>
</template>

<style scoped></style>
