<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, nextTick, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import { TagIcon } from '@heroicons/vue/20/solid';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Client,
    CreateTimeEntryBody,
} from '@/packages/api/src';
import { getOrganizationCurrencyString } from '@/utils/money';
import { canCreateProjects } from '@/utils/permissions';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import { Badge } from '@/packages/ui/src';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import DurationHumanInput from '@/packages/ui/src/Input/DurationHumanInput.vue';

import { InformationCircleIcon } from '@heroicons/vue/20/solid';
import type { Tag, Task } from '@/packages/api/src';
import TimePickerSimple from "@/packages/ui/src/Input/TimePickerSimple.vue";

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    enableEstimatedTime: boolean;
    createTimeEntry: (
        entry: Omit<CreateTimeEntryBody, 'member_id'>
    ) => Promise<void>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
    tags: Tag[];
    projects: Project[];
    tasks: Task[];
    clients: Client[];
}>();

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
    start: getDayJsInstance().utc().subtract(1, 'h').format(),
    end: getDayJsInstance().utc().format(),
};

const timeEntry = ref({ ...timeEntryDefaultValues });

watch(
    () => timeEntry.value.project_id,
    (value) => {
        if (value) {
            // check if project is billable by default and set billable accordingly
            const project = props.projects.find((p) => p.id === value);
            if (project) {
                timeEntry.value.billable = project.is_billable;
            }
        }
    }
);

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
    await props.createTimeEntry({ ...timeEntry.value });
    timeEntry.value = { ...timeEntryDefaultValues };
    localStart.value = getLocalizedDayJs(timeEntryDefaultValues.start).format();
    localEnd.value = getLocalizedDayJs(timeEntryDefaultValues.end).format();
    show.value = false;
}

const billableProxy = computed({
    get: () => (timeEntry.value.billable ? 'true' : 'false'),
    set: (value: string) => {
        timeEntry.value.billable = value === 'true';
    },
});

type BillableOption = {
    label: string;
    value: string;
};
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
                    <TextInput
                        id="description"
                        ref="description"
                        v-model="timeEntry.description"
                        placeholder="What did you work on?"
                        type="text"
                        class="mt-1 block w-full"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div
                class="sm:flex justify-between items-end space-y-2 sm:space-y-0 pt-4 sm:space-x-4">
                <div class="flex w-full items-center space-x-2 justify-between">
                    <div class="flex-1 min-w-0">
                        <TimeTrackerProjectTaskDropdown
                            v-model:project="timeEntry.project_id"
                            v-model:task="
                                timeEntry.task_id
                            "
                            :clients
                            :create-project
                            :create-client
                            :can-create-project="canCreateProjects()"
                            :currency="getOrganizationCurrencyString()"
                            size="xlarge"
                            class="bg-input-background"
                            :projects="projects"
                            :tasks="tasks"
                            :enable-estimated-time="enableEstimatedTime"></TimeTrackerProjectTaskDropdown>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex-col">
                            <TagDropdown
                                v-model="timeEntry.tags"
                                :create-tag
                                :tags="tags">
                                <template #trigger>
                                    <Badge
                                        class="bg-input-background"
                                        tag="button"
                                        size="xlarge">
                                        <TagIcon
                                            v-if="timeEntry.tags.length === 0"
                                            class="w-4"></TagIcon>
                                        <div
                                            v-else
                                            class="bg-accent-300/20 w-5 h-5 font-medium rounded flex items-center transition justify-center">
                                            {{ timeEntry.tags.length }}
                                        </div>
                                        <span>Tags</span>
                                    </Badge>
                                </template>
                            </TagDropdown>
                        </div>
                        <div class="flex-col">
                            <SelectDropdown
                                v-model="billableProxy"
                                :get-key-from-item="
                                    (item: BillableOption) => item.value
                                "
                                :get-name-for-item="
                                    (item: BillableOption) => item.label
                                "
                                :items="[
                                    {
                                        label: 'Billable',
                                        value: 'true',
                                    },
                                    {
                                        label: 'Non Billable',
                                        value: 'false',
                                    },
                                ]">
                                <template #trigger>
                                    <Badge
                                        class="bg-input-background"
                                        tag="button"
                                        size="xlarge">
                                        <BillableIcon
                                            class="h-4"></BillableIcon>
                                        <span>{{
                                            timeEntry.billable
                                                ? 'Billable'
                                                : 'Non-Billable'
                                        }}</span>
                                    </Badge>
                                </template>
                            </SelectDropdown>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex pt-4 space-x-4">
                <div class="flex-1">
                    <InputLabel>Duration</InputLabel>
                    <div class="space-y-2 mt-1 flex flex-col">
                        <DurationHumanInput
                            v-model:start="localStart"
                            v-model:end="localEnd"
                            name="Duration"></DurationHumanInput>
                        <div class="text-sm flex space-x-1">
                            <InformationCircleIcon
                                class="w-4 text-text-quaternary"></InformationCircleIcon>
                            <span class="text-text-secondary text-xs">
                                You can type natural language here f.e.
                                <span class="font-semibold"> 2h 30m</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="">
                    <InputLabel>Start</InputLabel>
                    <div class="flex flex-col items-center space-y-2 mt-1">
                        <TimePickerSimple

                            v-model="localStart"
                            size="large"></TimePickerSimple>
                        <DatePicker
                            v-model="localStart"
                            tabindex="1"
                            class="text-xs text-text-tertiary max-w-28 px-1.5 py-1.5"></DatePicker>
                    </div>
                </div>
                <div class="">
                    <InputLabel>End</InputLabel>
                    <div class="flex flex-col items-center space-y-2 mt-1">
                        <TimePickerSimple
                            v-model="localEnd"
                            size="large"></TimePickerSimple>
                        <DatePicker
                            v-model="localEnd"
                            tabindex="1"
                            class="text-xs text-text-tertiary max-w-28 px-1.5 py-1.5"></DatePicker>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton tabindex="2" @click="show = false"> Cancel</SecondaryButton>
            <PrimaryButton
                tabindex="2"
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
