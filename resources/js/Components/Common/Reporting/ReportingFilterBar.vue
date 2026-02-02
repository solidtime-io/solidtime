<script setup lang="ts">
import { CheckCircleIcon, TagIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import { FolderIcon } from '@heroicons/vue/16/solid';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import ReportingRoundingControls from '@/Components/Common/Reporting/ReportingRoundingControls.vue';
import TaskMultiselectDropdown from '@/Components/Common/Task/TaskMultiselectDropdown.vue';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ProjectMultiselectDropdown from '@/Components/Common/Project/ProjectMultiselectDropdown.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import DateRangePicker from '@/packages/ui/src/Input/DateRangePicker.vue';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useTagsStore } from '@/utils/useTags';

type TimeEntryRoundingType = 'up' | 'down' | 'nearest';

const selectedMembers = defineModel<string[]>('selectedMembers', { required: true });
const selectedProjects = defineModel<string[]>('selectedProjects', { required: true });
const selectedTasks = defineModel<string[]>('selectedTasks', { required: true });
const selectedClients = defineModel<string[]>('selectedClients', { required: true });
const selectedTags = defineModel<string[]>('selectedTags', { required: true });
const billable = defineModel<'true' | 'false' | null>('billable', { required: true });
const roundingEnabled = defineModel<boolean>('roundingEnabled', { required: true });
const roundingType = defineModel<TimeEntryRoundingType>('roundingType', { required: true });
const roundingMinutes = defineModel<number>('roundingMinutes', { required: true });
const startDate = defineModel<string>('startDate', { required: true });
const endDate = defineModel<string>('endDate', { required: true });

const emit = defineEmits<{
    submit: [];
}>();

const { tags } = useTagsQuery();

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}
</script>

<template>
    <div class="py-2.5 w-full border-b border-default-background-separator">
        <MainContainer class="sm:flex space-y-4 sm:space-y-0 justify-between">
            <div class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-3">
                <div class="text-sm font-medium">Filters</div>
                <MemberMultiselectDropdown v-model="selectedMembers" @submit="emit('submit')">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedMembers.length"
                            :active="selectedMembers.length > 0"
                            title="Members"
                            :icon="UserGroupIcon" />
                    </template>
                </MemberMultiselectDropdown>
                <ProjectMultiselectDropdown v-model="selectedProjects" @submit="emit('submit')">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedProjects.length"
                            :active="selectedProjects.length > 0"
                            title="Projects"
                            :icon="FolderIcon" />
                    </template>
                </ProjectMultiselectDropdown>
                <TaskMultiselectDropdown v-model="selectedTasks" @submit="emit('submit')">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedTasks.length"
                            :active="selectedTasks.length > 0"
                            title="Tasks"
                            :icon="CheckCircleIcon" />
                    </template>
                </TaskMultiselectDropdown>
                <ClientMultiselectDropdown v-model="selectedClients" @submit="emit('submit')">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedClients.length"
                            :active="selectedClients.length > 0"
                            title="Clients"
                            :icon="FolderIcon" />
                    </template>
                </ClientMultiselectDropdown>
                <TagDropdown
                    v-model="selectedTags"
                    :create-tag
                    :tags="tags"
                    @submit="emit('submit')">
                    <template #trigger>
                        <ReportingFilterBadge
                            :count="selectedTags.length"
                            :active="selectedTags.length > 0"
                            title="Tags"
                            :icon="TagIcon" />
                    </template>
                </TagDropdown>

                <Select v-model="billable" @update:model-value="emit('submit')">
                    <SelectTrigger
                        size="small"
                        variant="outline"
                        :active="billable !== null"
                        :show-chevron="false">
                        <SelectValue class="flex items-center gap-2">
                            <BillableIcon
                                class="h-4"
                                :class="
                                    billable !== null
                                        ? 'dark:text-accent-300/80 text-accent-400/80'
                                        : 'text-text-quaternary'
                                " />
                            <span class="text-text-secondary">{{
                                billable === 'false' ? 'Non Billable' : 'Billable'
                            }}</span>
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="null">Both</SelectItem>
                        <SelectItem value="true">Billable</SelectItem>
                        <SelectItem value="false">Non Billable</SelectItem>
                    </SelectContent>
                </Select>
                <ReportingRoundingControls
                    v-model:enabled="roundingEnabled"
                    v-model:type="roundingType"
                    v-model:minutes="roundingMinutes"
                    @change="emit('submit')" />
            </div>
            <div>
                <DateRangePicker
                    v-model:start="startDate"
                    v-model:end="endDate"
                    @submit="emit('submit')" />
            </div>
        </MainContainer>
    </div>
</template>
