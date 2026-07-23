<script setup lang="ts">
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import TimeTrackerTagDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerTagDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import type {
    Client,
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
} from '@/packages/api/src';

const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', { required: true });

const props = defineProps<{
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    currency: string;
    organizationBillableRate: number | null;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();

const emit = defineEmits<{ updateTimeEntry: [] }>();

function updateProject() {
    // Adopt the project's billable default when a project is picked.
    const project = props.projects.find((p) => p.id === currentTimeEntry.value.project_id);
    if (project) {
        currentTimeEntry.value.billable = project.is_billable;
    }
    emit('updateTimeEntry');
}
</script>

<template>
    <div class="flex items-center w-[130px] @2xl:w-auto shrink min-w-0">
        <TimeTrackerProjectTaskDropdown
            v-model:project="currentTimeEntry.project_id"
            v-model:task="currentTimeEntry.task_id"
            variant="outline"
            :create-client="createClient"
            :can-create-project="canCreateProject"
            :clients="clients"
            :create-project="createProject"
            :currency="currency"
            :organization-billable-rate="organizationBillableRate"
            :projects="projects"
            :tasks="tasks"
            :enable-estimated-time="enableEstimatedTime"
            @changed="updateProject"></TimeTrackerProjectTaskDropdown>
    </div>
    <div class="flex items-center space-x-0 @4xl:space-x-2 px-2 @4xl:px-4 shrink-0">
        <TimeTrackerTagDropdown
            v-model="currentTimeEntry.tags"
            :create-tag="createTag"
            :tags="tags"
            @changed="emit('updateTimeEntry')"></TimeTrackerTagDropdown>
        <BillableToggleButton
            v-model="currentTimeEntry.billable"
            @changed="emit('updateTimeEntry')"></BillableToggleButton>
    </div>
</template>
