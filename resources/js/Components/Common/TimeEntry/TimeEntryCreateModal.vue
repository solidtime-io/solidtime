<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { nextTick, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeTrackerTagDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerTagDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import { getCurrentUserId } from '@/utils/useUser';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { storeToRefs } from 'pinia';
import { useTasksStore } from '@/utils/useTasks';
import { useProjectsStore } from '@/utils/useProjects';
import { useTagsStore } from '@/utils/useTags';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Client,
} from '@/packages/api/src';
import { useClientsStore } from '@/utils/useClients';
import TimePicker from '@/packages/ui/src/Input/TimePicker.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import { canCreateProjects } from '@/utils/permissions';
const projectStore = useProjectsStore();
const { projects } = storeToRefs(projectStore);
const taskStore = useTasksStore();
const { tasks } = storeToRefs(taskStore);
const clientStore = useClientsStore();
const { clients } = storeToRefs(clientStore);

const { createTimeEntry } = useTimeEntriesStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

defineProps<{
    enableEstimatedTime: boolean;
}>();

async function createProject(
    project: CreateProjectBody
): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(
    body: CreateClientBody
): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

const description = ref<HTMLInputElement | null>(null);

watch(show, (value) => {
    if (value) {
        nextTick(() => {
            description.value?.focus();
        });
    }
});

const timeEntryDefaultValues = {
    description: '',
    project_id: null,
    task_id: null,
    tags: [],
    billable: false,
    start: getDayJsInstance().utc().format(),
    end: getDayJsInstance().utc().format(),
    user_id: getCurrentUserId(),
};

const timeEntry = ref({ ...timeEntryDefaultValues });

const localStart = ref(
    getLocalizedDayJs(timeEntryDefaultValues.start).format()
);

const localEnd = ref(getLocalizedDayJs(timeEntryDefaultValues.end).format());

watch(localStart, (value) => {
    timeEntry.value.start = getLocalizedDayJs(value).utc().format();
    if (getLocalizedDayJs(localEnd.value).isBefore(getLocalizedDayJs(value))) {
        localEnd.value = value;
    }
});

watch(localEnd, (value) => {
    timeEntry.value.end = getLocalizedDayJs(value).utc().format();
});

async function submit() {
    await createTimeEntry(timeEntry.value);
    timeEntry.value = { ...timeEntryDefaultValues };
    localStart.value = getLocalizedDayJs(timeEntryDefaultValues.start).format();
    localEnd.value = getLocalizedDayJs(timeEntryDefaultValues.end).format();
    show.value = false;
}
const { tags } = storeToRefs(useTagsStore());
async function createTag(tag: string) {
    return await useTagsStore().createTag(tag);
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create manual time entry </span>
            </div>
        </template>

        <template #content>
            <div class="sm:flex items-end space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex-1">
                    <InputLabel for="description" value="Description" />
                    <TextInput
                        id="description"
                        ref="description"
                        v-model="timeEntry.description"
                        @keydown.enter="submit"
                        type="text"
                        class="mt-1 block w-full" />
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <TimeTrackerProjectTaskDropdown
                            :clients
                            :createProject
                            :createClient
                            :canCreateProject="canCreateProjects()"
                            :currency="getOrganizationCurrencyString()"
                            class="mt-1"
                            size="xlarge"
                            :projects="projects"
                            :tasks="tasks"
                            :enableEstimatedTime="enableEstimatedTime"
                            v-model:project="timeEntry.project_id"
                            v-model:task="
                                timeEntry.task_id
                            "></TimeTrackerProjectTaskDropdown>
                    </div>
                    <div class="flex items-center space-x-2 px-4">
                        <TimeTrackerTagDropdown
                            :tags="tags"
                            :createTag="createTag"
                            v-model="timeEntry.tags"></TimeTrackerTagDropdown>
                        <BillableToggleButton
                            v-model="timeEntry.billable"></BillableToggleButton>
                    </div>
                </div>
            </div>
            <div class="flex pt-4">
                <div class="flex-1">
                    <InputLabel>Start</InputLabel>
                    <div class="flex items-center space-x-4 mt-1">
                        <DatePicker v-model="localStart"></DatePicker>
                        <TimePicker
                            size="large"
                            v-model="localStart"></TimePicker>
                    </div>
                </div>
                <div class="flex-1">
                    <InputLabel>End</InputLabel>
                    <div class="flex items-center space-x-4 mt-1">
                        <DatePicker v-model="localEnd"></DatePicker>
                        <TimePicker
                            size="large"
                            v-model="localEnd"></TimePicker>
                    </div>
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
                Create Time Entry
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
