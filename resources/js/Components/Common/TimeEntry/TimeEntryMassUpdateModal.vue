<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, nextTick, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';

import { storeToRefs } from 'pinia';
import { useTasksStore } from '@/utils/useTasks';
import { useProjectsStore } from '@/utils/useProjects';
import { useTagsStore } from '@/utils/useTags';
import {
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
    type Client,
    api,
    type TimeEntry,
    type UpdateMultipleTimeEntriesChangeset,
} from '@/packages/api/src';
import { useClientsStore } from '@/utils/useClients';
import { getOrganizationCurrencyString } from '@/utils/money';
import { Badge } from '@/packages/ui/src';
import SelectDropdown from '../../../packages/ui/src/Input/SelectDropdown.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
const projectStore = useProjectsStore();
const { projects } = storeToRefs(projectStore);
const taskStore = useTasksStore();
const { tasks } = storeToRefs(taskStore);
const clientStore = useClientsStore();
const { clients } = storeToRefs(clientStore);

const show = defineModel('show', { default: false });
const saving = ref(false);

async function createProject(
    project: CreateProjectBody
): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

const props = defineProps<{
    timeEntries: TimeEntry[];
}>();

const emit = defineEmits<{
    submit: [];
}>();

async function createClient(
    body: CreateClientBody
): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

const descriptionInput = ref<HTMLInputElement | null>(null);

const { handleApiRequestNotifications } = useNotificationsStore();

watch(show, (value) => {
    if (value) {
        nextTick(() => {
            descriptionInput.value?.focus();
        });
    }
});

const description = ref<string>('');
const taskId = ref<string | null | undefined>(undefined);
const projectId = ref<string | null | undefined>(undefined);
const billable = ref<boolean | undefined>(undefined);
const selectedTags = ref<string[]>([]);

const { tags } = storeToRefs(useTagsStore());
async function createTag(tag: string) {
    return await useTagsStore().createTag(tag);
}

const timeEntryBillable = computed({
    get: () => {
        if (billable.value) {
            return 'do-not-update';
        }
        return billable.value ? 'billable' : 'non-billable';
    },
    set: (value) => {
        if (value === 'do-not-update') {
            billable.value = undefined;
        } else if (value === 'billable') {
            billable.value = true;
        } else {
            billable.value = false;
        }
    },
});

async function submit() {
    const organizationId = getCurrentOrganizationId();
    saving.value = true;
    if (organizationId) {
        const timeEntryUpdatesBody = {} as UpdateMultipleTimeEntriesChangeset;
        if (description.value && description.value !== '') {
            timeEntryUpdatesBody.description = description.value;
        }
        if (projectId.value !== undefined) {
            timeEntryUpdatesBody.project_id = projectId.value;
            timeEntryUpdatesBody.task_id = null;
        }
        if (taskId.value !== undefined) {
            timeEntryUpdatesBody.task_id = taskId.value;
        }
        if (billable.value !== undefined) {
            timeEntryUpdatesBody.billable = billable.value;
        }
        if (selectedTags.value.length > 0) {
            timeEntryUpdatesBody.tags = selectedTags.value;
        }

        try {
            await handleApiRequestNotifications(
                () =>
                    api.updateMultipleTimeEntries(
                        {
                            ids: props.timeEntries.map(
                                (timeEntry) => timeEntry.id
                            ),
                            changes: {
                                ...timeEntryUpdatesBody,
                            },
                        },
                        {
                            params: {
                                organization: organizationId,
                            },
                        }
                    ),
                'Time entries updated',
                'Failed to update time entries',
                () => {
                    show.value = false;
                    emit('submit');
                    description.value = '';
                    projectId.value = undefined;
                    taskId.value = undefined;
                    selectedTags.value = [];
                    billable.value = undefined;
                    saving.value = false;
                }
            );
        } catch (e) {
            saving.value = false;
        }
    }
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update {{ timeEntries.length }} time entries </span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4">
                <div class="space-y-2">
                    <InputLabel for="description" value="Description" />
                    <TextInput
                        id="description"
                        ref="descriptionInput"
                        v-model="description"
                        @keydown.enter="submit"
                        type="text"
                        class="mt-1 block w-full" />
                </div>
                <div class="space-y-2">
                    <InputLabel for="project" value="Project" />
                    <TimeTrackerProjectTaskDropdown
                        :clients
                        :createProject
                        :createClient
                        :currency="getOrganizationCurrencyString()"
                        class="mt-1"
                        size="xlarge"
                        :projects="projects"
                        :tasks="tasks"
                        v-model:project="projectId"
                        v-model:task="taskId"></TimeTrackerProjectTaskDropdown>
                </div>
                <div class="space-y-2">
                    <InputLabel for="project" value="Tag" />
                    <TagDropdown :createTag v-model="selectedTags" :tags="tags">
                        <template #trigger>
                            <Badge size="xlarge">
                                <span v-if="selectedTags.length > 0">
                                    Set {{ selectedTags.length }} tags
                                </span>
                                <span v-else> Select Tags... </span>
                            </Badge>
                        </template>
                    </TagDropdown>
                </div>
                <div class="space-y-2">
                    <InputLabel for="project" value="Billable" />
                    <SelectDropdown
                        v-model="timeEntryBillable"
                        :get-key-from-item="(item) => item.value"
                        :get-name-for-item="(item) => item.label"
                        :items="[
                            {
                                label: 'Keep current billable status',
                                value: 'do-not-update',
                            },
                            {
                                label: 'Billable',
                                value: 'billable',
                            },
                            {
                                label: 'Non Billable',
                                value: 'non-billable',
                            },
                        ]">
                        <template v-slot:trigger>
                            <Badge tag="button" size="xlarge">
                                <span v-if="billable === undefined">
                                    Set billable status
                                </span>
                                <span v-else-if="billable === true">
                                    Billable
                                </span>
                                <span v-else> Non Billable </span></Badge
                            >
                        </template>
                    </SelectDropdown>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Update Time Entries
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
