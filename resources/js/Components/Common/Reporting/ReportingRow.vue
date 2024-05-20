<script setup lang="ts">
import { formatHumanReadableDuration } from '@/utils/time';
import { formatMoney } from '@/utils/money';
import GroupedItemsCountButton from '@/Components/Common/GroupedItemsCountButton.vue';
import { computed, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import { storeToRefs } from 'pinia';
import { useMembersStore } from '@/utils/useMembers';
import { useTasksStore } from '@/utils/useTasks';
import { twMerge } from 'tailwind-merge';

type AggregatedGroupedData = GroupedData & {
    grouped_data?: GroupedData[] | null;
};

type GroupedData = {
    key: string | null;
    seconds: number;
    cost: number;
    type: string;
};

const props = defineProps<{
    entry: AggregatedGroupedData;
    indent?: boolean;
}>();

const emptyPlaceholder = computed(() => {
    const emptyPlaceholder = {
        user: 'No User',
        project: 'No Project',
        task: 'No Task',
        billable: 'Non-Billable',
    };

    return emptyPlaceholder[props.entry.type as keyof typeof emptyPlaceholder];
});

function getNameForKey(key: string) {
    if (props.entry.type === 'project') {
        const projectsStore = useProjectsStore();
        const { projects } = storeToRefs(projectsStore);
        return projects.value.find((project) => project.id === key)?.name;
    }
    if (props.entry.type === 'user') {
        const memberStore = useMembersStore();
        const { members } = storeToRefs(memberStore);
        return members.value.find((member) => member.user_id === key)?.name;
    }
    if (props.entry.type === 'task') {
        const taskStore = useTasksStore();
        const { tasks } = storeToRefs(taskStore);
        return tasks.value.find((task) => task.id === key)?.name;
    }
    if (props.entry.type === 'billable') {
        if (key === '0') {
            return 'Non-Billable';
        } else {
            return 'Billable';
        }
    }
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
                {{ entry.key ? getNameForKey(entry.key) : emptyPlaceholder }}
            </span>
        </div>
        <div class="justify-end flex items-center">
            {{ formatHumanReadableDuration(entry.seconds) }}
        </div>
        <div class="justify-end pr-6 flex items-center">
            {{ formatMoney(entry.cost) }}
        </div>
    </div>
    <div
        class="col-span-3 grid bg-quaternary"
        style="grid-template-columns: 1fr 150px 150px"
        v-if="expanded && entry.grouped_data">
        <ReportingRow
            indent
            v-for="subEntry in entry.grouped_data"
            :key="subEntry.key ?? 'none'"
            :entry="subEntry"></ReportingRow>
    </div>
</template>

<style scoped></style>
