<script setup lang="ts">
import { computed } from 'vue';
import { CheckCircleIcon, TagIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import { FolderIcon, UserCircleIcon } from '@heroicons/vue/16/solid';
import { Check, Coffee } from '@lucide/vue';
import { RadioGroupIndicator, RadioGroupItem, RadioGroupRoot, type AcceptableValue } from 'reka-ui';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/packages/ui/src';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import type { GoalFormData } from '@/Components/Common/Goal/goalForm';
import type { TagMatchType } from '@/types/reporting';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useTagsStore } from '@/utils/useTags';
import { useBreaksEnabled } from '@/packages/ui/src/utils/useBreaksEnabled';

type FilterOption = { id: string; name: string };

const model = defineModel<GoalFormData>({ required: true });

defineProps<{
    // Members that can be selected in the member filter, the filter is hidden without them
    memberOptions?: FilterOption[] | null;
}>();

const breaksEnabled = useBreaksEnabled();
const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();
const { tags } = useTagsQuery();

// Client and project filters are ANDed, so only offer projects of the selected clients. Projects
// that are already selected stay listed so they can still be unselected.
const visibleProjects = computed(() => {
    const clientIds = model.value.client_ids;
    if (clientIds.length === 0) return projects.value;
    return projects.value.filter(
        (project) =>
            (project.client_id !== null && clientIds.includes(project.client_id)) ||
            model.value.project_ids.includes(project.id)
    );
});
// Project and task filters are ANDed, so only offer tasks from the selected projects. Tasks
// that are already selected stay listed so they can still be unselected.
const visibleTasks = computed(() => {
    const projectIds = model.value.project_ids;
    if (projectIds.length === 0) return tasks.value;
    return tasks.value.filter(
        (task) => projectIds.includes(task.project_id) || model.value.task_ids.includes(task.id)
    );
});

function getKeyFromItem(item: FilterOption) {
    return item.id;
}

function getNameForItem(item: FilterOption) {
    return item.name;
}

const tagMatchOptions: { value: TagMatchType; label: string }[] = [
    { value: 'contains', label: 'Contains' },
    { value: 'not_contains', label: 'Does Not Contain' },
];

function selectTagMatchType(value: AcceptableValue) {
    model.value.tag_match_type = value as TagMatchType;
}

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}
</script>

<template>
    <div class="space-y-1.5">
        <div class="flex flex-wrap items-center gap-2" data-testid="goal_filters">
            <MultiselectDropdown
                v-if="memberOptions"
                v-model="model.member_ids"
                search-placeholder="Search for a Member..."
                :items="memberOptions"
                :get-key-from-item="getKeyFromItem"
                :get-name-for-item="getNameForItem">
                <template #trigger>
                    <ReportingFilterBadge
                        data-testid="goal_filter_member_ids"
                        :count="model.member_ids.length"
                        :active="model.member_ids.length > 0"
                        title="Members"
                        :icon="UserGroupIcon" />
                </template>
            </MultiselectDropdown>
            <MultiselectDropdown
                v-model="model.project_ids"
                search-placeholder="Search for a Project..."
                :items="visibleProjects"
                :get-key-from-item="getKeyFromItem"
                :get-name-for-item="getNameForItem"
                no-item-label="No Project">
                <template #trigger>
                    <ReportingFilterBadge
                        data-testid="goal_filter_project_ids"
                        :count="model.project_ids.length"
                        :active="model.project_ids.length > 0"
                        title="Projects"
                        :icon="FolderIcon" />
                </template>
            </MultiselectDropdown>
            <MultiselectDropdown
                v-model="model.task_ids"
                search-placeholder="Search for a Task..."
                :items="visibleTasks"
                :get-key-from-item="getKeyFromItem"
                :get-name-for-item="getNameForItem"
                no-item-label="No Task">
                <template #trigger>
                    <ReportingFilterBadge
                        data-testid="goal_filter_task_ids"
                        :count="model.task_ids.length"
                        :active="model.task_ids.length > 0"
                        title="Tasks"
                        :icon="CheckCircleIcon" />
                </template>
            </MultiselectDropdown>
            <MultiselectDropdown
                v-model="model.client_ids"
                search-placeholder="Search for a Client..."
                :items="clients"
                :get-key-from-item="getKeyFromItem"
                :get-name-for-item="getNameForItem"
                no-item-label="No Client">
                <template #trigger>
                    <ReportingFilterBadge
                        data-testid="goal_filter_client_ids"
                        :count="model.client_ids.length"
                        :active="model.client_ids.length > 0"
                        title="Clients"
                        :icon="UserCircleIcon" />
                </template>
            </MultiselectDropdown>
            <TagDropdown v-model="model.tag_ids" :create-tag :tags="tags">
                <template #trigger>
                    <ReportingFilterBadge
                        data-testid="goal_filter_tag_ids"
                        :count="model.tag_ids.length"
                        :active="model.tag_ids.length > 0"
                        title="Tags"
                        :icon="TagIcon" />
                </template>
                <template #content-before-list>
                    <div class="mt-2 border-b border-card-background-separator pb-2">
                        <div
                            id="goal-tag-match-type-label"
                            class="mb-1.5 px-2 text-xs font-medium text-text-tertiary uppercase">
                            Match
                        </div>
                        <RadioGroupRoot
                            :model-value="model.tag_match_type"
                            aria-labelledby="goal-tag-match-type-label"
                            class="space-y-1"
                            @update:model-value="selectTagMatchType">
                            <RadioGroupItem
                                v-for="option in tagMatchOptions"
                                :key="option.value"
                                :value="option.value"
                                class="relative flex w-full items-center rounded-md py-1.5 pl-2 pr-8 text-left text-sm font-medium text-text-secondary hover:bg-card-background-active data-[state=checked]:text-text-primary">
                                {{ option.label }}
                                <span
                                    class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                                    <RadioGroupIndicator>
                                        <Check class="h-4 w-4" />
                                    </RadioGroupIndicator>
                                </span>
                            </RadioGroupItem>
                        </RadioGroupRoot>
                    </div>
                </template>
            </TagDropdown>
            <Select v-model="model.billable">
                <SelectTrigger
                    size="sm"
                    variant="outline"
                    :active="model.billable !== null"
                    :show-chevron="false">
                    <SelectValue class="flex items-center gap-2">
                        <BillableIcon
                            class="h-4"
                            :class="
                                model.billable !== null
                                    ? 'dark:text-accent-300/80 text-accent-400/80'
                                    : 'text-text-quaternary'
                            " />
                        <span class="text-text-secondary">{{
                            model.billable === 'false' ? 'Non Billable' : 'Billable'
                        }}</span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="null">Both</SelectItem>
                    <SelectItem value="true">Billable</SelectItem>
                    <SelectItem value="false">Non Billable</SelectItem>
                </SelectContent>
            </Select>
            <Select v-if="breaksEnabled" v-model="model.time_entry_type">
                <SelectTrigger
                    size="sm"
                    variant="outline"
                    :active="model.time_entry_type !== null"
                    :show-chevron="false">
                    <SelectValue class="flex items-center gap-2">
                        <Coffee
                            class="h-4 w-4"
                            :class="
                                model.time_entry_type !== null
                                    ? 'dark:text-accent-300/80 text-accent-400/80'
                                    : 'text-text-quaternary'
                            " />
                        <span class="text-text-secondary">{{
                            model.time_entry_type === null
                                ? 'Type'
                                : model.time_entry_type === 'break'
                                  ? 'Breaks'
                                  : 'Work time'
                        }}</span>
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="null">Both</SelectItem>
                    <SelectItem value="work">Work time</SelectItem>
                    <SelectItem value="break">Breaks</SelectItem>
                </SelectContent>
            </Select>
        </div>
        <p v-if="memberOptions && model.member_ids.length > 0" class="text-xs text-text-tertiary">
            Selected members do not get access to the goal.
        </p>
    </div>
</template>
