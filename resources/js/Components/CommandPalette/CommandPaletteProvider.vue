<script setup lang="ts">
import { onMounted, onUnmounted, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { CommandPalette } from '@/packages/ui/src/CommandPalette';
import { useCommandPalette } from '@/utils/useCommandPalette';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useTagsStore } from '@/utils/useTags';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';
import type {
    CreateClientBody,
    CreateProjectBody,
    CreateTimeEntryBody,
    Project,
    Client,
    Tag,
} from '@/packages/api/src';
import type { User } from '@/types/models';
import type { Role } from '@/types/jetstream';

// Import modals
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import TaskCreateModal from '@/Components/Common/Task/TaskCreateModal.vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import MemberInviteModal from '@/Components/Common/Member/MemberInviteModal.vue';
import TimeEntryCreateModal from '@/packages/ui/src/TimeEntry/TimeEntryCreateModal.vue';

// Import dropdowns for active timer selectors
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';

// Dialog components for selectors
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';

const {
    isOpen,
    searchTerm,
    groups,
    entityResults,
    togglePalette,
    showCreateProjectModal,
    showCreateClientModal,
    showCreateTaskModal,
    showCreateTagModal,
    showInviteMemberModal,
    showCreateTimeEntryModal,
    showProjectSelector,
    showTaskSelector,
    showTagsSelector,
    currentTimeEntry,
    updateTimer,
    projects,
    clients,
    tasks,
    tags,
} = useCommandPalette();

// Stores for creating entities
const projectsStore = useProjectsStore();
const clientsStore = useClientsStore();
const tagsStore = useTagsStore();

// Time entry mutations
const { createTimeEntry: createTimeEntryMutation } = useTimeEntriesMutations();

// Get available roles from page props (for member invite modal)
const page = usePage<{
    availableRoles?: Role[];
    auth: {
        user: User;
    };
}>();

const availableRoles = computed(() => page.props.availableRoles ?? []);

// Active clients for dropdowns
const activeClients = computed(() => clients.value.filter((c) => !c.is_archived));

// Keyboard shortcut handler
function handleKeyDown(e: KeyboardEvent) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        togglePalette();
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown);
});

// Project creation
async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    const openedFromCommandPalette = showCreateProjectModal.value;
    const newProject = await projectsStore.createProject(project);
    showCreateProjectModal.value = false;
    if (newProject && openedFromCommandPalette) {
        router.visit(route('projects.show', { project: newProject.id }));
    }
    return newProject;
}

async function createClient(client: CreateClientBody): Promise<Client | undefined> {
    const openedFromCommandPalette = showCreateClientModal.value;
    const newClient = await clientsStore.createClient(client);
    if (newClient && openedFromCommandPalette) {
        showCreateClientModal.value = false;
        router.visit(route('clients'));
    }
    return newClient;
}

async function createTag(name: string): Promise<Tag | undefined> {
    const openedFromCommandPalette = showCreateTagModal.value;
    const newTag = await tagsStore.createTag(name);
    if (newTag && openedFromCommandPalette) {
        showCreateTagModal.value = false;
        router.visit(route('tags'));
    }
    return newTag;
}

async function createTimeEntry(timeEntry: Omit<CreateTimeEntryBody, 'member_id'>) {
    await createTimeEntryMutation(timeEntry);
    showCreateTimeEntryModal.value = false;
}

async function handleProjectTaskSelect() {
    showProjectSelector.value = false;
    showTaskSelector.value = false;
    await updateTimer();
}

async function handleTagsSelect() {
    showTagsSelector.value = false;
    await updateTimer();
}

const firstProjectId = computed(() => projects.value[0]?.id ?? '');
</script>

<template>
    <!-- Command Palette Dialog -->
    <CommandPalette
        v-model:open="isOpen"
        v-model:search-term="searchTerm"
        :groups="groups"
        :entity-results="entityResults" />

    <!-- Project Create Modal -->
    <ProjectCreateModal
        v-model:show="showCreateProjectModal"
        :create-project="createProject"
        :create-client="createClient"
        :clients="activeClients"
        :currency="getOrganizationCurrencyString()"
        :enable-estimated-time="isAllowedToPerformPremiumAction()" />

    <!-- Client Create Modal -->
    <ClientCreateModal v-model:show="showCreateClientModal" />

    <!-- Task Create Modal -->
    <TaskCreateModal
        v-if="firstProjectId"
        v-model:show="showCreateTaskModal"
        :project-id="firstProjectId" />

    <!-- Tag Create Modal -->
    <TagCreateModal v-model:show="showCreateTagModal" :create-tag="createTag" />

    <!-- Member Invite Modal -->
    <MemberInviteModal v-model:show="showInviteMemberModal" :available-roles="availableRoles" />

    <!-- Time Entry Create Modal -->
    <TimeEntryCreateModal
        v-model:show="showCreateTimeEntryModal"
        :create-time-entry="createTimeEntry"
        :create-project="createProject"
        :create-client="createClient"
        :create-tag="createTag"
        :projects="projects"
        :tasks="tasks"
        :tags="tags"
        :clients="activeClients"
        :currency="getOrganizationCurrencyString()"
        :enable-estimated-time="isAllowedToPerformPremiumAction()"
        :can-create-project="canCreateProjects()" />

    <!-- Project Selector Dialog for Active Timer -->
    <DialogModal :show="showProjectSelector" closeable @close="showProjectSelector = false">
        <template #title>Set Project</template>
        <template #content>
            <TimeTrackerProjectTaskDropdown
                v-model:project="currentTimeEntry.project_id"
                v-model:task="currentTimeEntry.task_id"
                variant="outline"
                :projects="projects"
                :tasks="tasks"
                :clients="activeClients"
                :create-project="createProject"
                :create-client="createClient"
                :can-create-project="canCreateProjects()"
                :currency="getOrganizationCurrencyString()"
                :enable-estimated-time="isAllowedToPerformPremiumAction()"
                class="w-full" />
        </template>
        <template #footer>
            <SecondaryButton @click="showProjectSelector = false"> Cancel </SecondaryButton>
            <SecondaryButton class="ms-3" @click="handleProjectTaskSelect"> Save </SecondaryButton>
        </template>
    </DialogModal>

    <!-- Task Selector Dialog for Active Timer -->
    <DialogModal :show="showTaskSelector" closeable @close="showTaskSelector = false">
        <template #title>Set Task</template>
        <template #content>
            <TimeTrackerProjectTaskDropdown
                v-model:project="currentTimeEntry.project_id"
                v-model:task="currentTimeEntry.task_id"
                variant="outline"
                :projects="projects"
                :tasks="tasks"
                :clients="activeClients"
                :create-project="createProject"
                :create-client="createClient"
                :can-create-project="canCreateProjects()"
                :currency="getOrganizationCurrencyString()"
                :enable-estimated-time="isAllowedToPerformPremiumAction()"
                class="w-full" />
        </template>
        <template #footer>
            <SecondaryButton @click="showTaskSelector = false"> Cancel </SecondaryButton>
            <SecondaryButton class="ms-3" @click="handleProjectTaskSelect"> Save </SecondaryButton>
        </template>
    </DialogModal>

    <!-- Tags Selector Dialog for Active Timer -->
    <DialogModal :show="showTagsSelector" closeable @close="showTagsSelector = false">
        <template #title>Set Tags</template>
        <template #content>
            <TagDropdown v-model="currentTimeEntry.tags" :tags="tags" :create-tag="createTag">
                <template #trigger>
                    <div
                        class="w-full p-3 border border-card-border rounded-lg cursor-pointer hover:bg-tertiary transition">
                        <span
                            v-if="currentTimeEntry.tags.length === 0"
                            class="text-muted-foreground">
                            Click to select tags...
                        </span>
                        <span v-else> {{ currentTimeEntry.tags.length }} tag(s) selected </span>
                    </div>
                </template>
            </TagDropdown>
        </template>
        <template #footer>
            <SecondaryButton @click="showTagsSelector = false"> Cancel </SecondaryButton>
            <SecondaryButton class="ms-3" @click="handleTagsSelect"> Save </SecondaryButton>
        </template>
    </DialogModal>
</template>
