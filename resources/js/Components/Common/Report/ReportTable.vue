<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { computed } from 'vue';
import { canCreateProjects } from '@/utils/permissions';
import type { Report } from '@/packages/api/src';
import ReportTableHeading from '@/Components/Common/Report/ReportTableHeading.vue';
import ReportTableRow from '@/Components/Common/Report/ReportTableRow.vue';
import { router } from '@inertiajs/vue3';

defineProps<{
    reports: Report[];
}>();

const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(150px, auto) minmax(250px, 1fr) minmax(140px, auto) minmax(130px, auto) 80px;`;
});
</script>

<template>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="report_table"
                class="grid min-w-full"
                :style="gridTemplate">
                <ReportTableHeading></ReportTableHeading>
                <div
                    class="col-span-5 py-24 text-center"
                    v-if="reports.length === 0">
                    <FolderPlusIcon
                        class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-white font-semibold">
                        No shared reports found
                    </h3>
                    <p class="pb-5" v-if="canCreateProjects()">
                        Create your first project now!
                    </p>
                    <SecondaryButton
                        @click="router.visit(route('reporting'))"
                        :icon="PlusIcon"
                        >Go to the overview to create a report
                    </SecondaryButton>
                </div>
                <template v-for="report in reports" :key="report.id">
                    <ReportTableRow :report="report"></ReportTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
