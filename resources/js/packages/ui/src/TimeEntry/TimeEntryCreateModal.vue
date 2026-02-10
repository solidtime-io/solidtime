<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, nextTick, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { Field, FieldLabel } from '../field';
import { TagIcon } from '@heroicons/vue/20/solid';
import { getDayJsInstance, getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Client,
    CreateTimeEntryBody,
} from '@/packages/api/src';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Button } from '@/packages/ui/src/Buttons';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import DurationHumanInput from '@/packages/ui/src/Input/DurationHumanInput.vue';

import { InformationCircleIcon } from '@heroicons/vue/20/solid';
import type { Tag, Task } from '@/packages/api/src';
import TimePickerSimple from '@/packages/ui/src/Input/TimePickerSimple.vue';

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    enableEstimatedTime: boolean;
    createTimeEntry: (entry: Omit<CreateTimeEntryBody, 'member_id'>) => Promise<void>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
    tags: Tag[];
    projects: Project[];
    tasks: Task[];
    clients: Client[];
    start?: string;
    end?: string;
    currency: string;
    canCreateProject: boolean;
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
    start: getDayJsInstance().utc().subtract(1, 'h').second(0).format(),
    end: getDayJsInstance().utc().second(0).format(),
};

const timeEntry = ref({
    ...timeEntryDefaultValues,
});

// update the localStart and localEnd when props.start or props.end get updates
watch(
    () => props.start,
    (value) => {
        if (value) {
            localStart.value = getLocalizedDayJs(value).format();
        }
    }
);
watch(
    () => props.end,
    (value) => {
        if (value) {
            localEnd.value = getLocalizedDayJs(value).format();
        }
    }
);

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

const localStart = ref(getLocalizedDayJs(timeEntryDefaultValues.start).format());

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
                        aria-label="Description"
                        placeholder="What did you work on?"
                        type="text"
                        class="mt-1 block w-full"
                        @keydown.enter="submit" />
                </div>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-end gap-2 pt-4">
                <div class="flex-1 min-w-0">
                    <TimeTrackerProjectTaskDropdown
                        v-model:project="timeEntry.project_id"
                        v-model:task="timeEntry.task_id"
                        variant="input"
                        size="default"
                        :clients
                        :create-project
                        :create-client
                        :can-create-project
                        :currency
                        :projects="projects"
                        :tasks="tasks"
                        :enable-estimated-time="enableEstimatedTime" />
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <TagDropdown
                        v-model="timeEntry.tags"
                        :create-tag
                        :tags="tags"
                        :show-no-tag-option="false">
                        <template #trigger>
                            <Button variant="input">
                                <TagIcon class="h-4 text-icon-default" />
                                <span>{{
                                    timeEntry.tags.length === 0
                                        ? 'Tags'
                                        : `${timeEntry.tags.length} Tag${timeEntry.tags.length > 1 ? 's' : ''}`
                                }}</span>
                            </Button>
                        </template>
                    </TagDropdown>
                    <Select v-model="billableProxy">
                        <SelectTrigger :show-chevron="false">
                            <SelectValue class="flex items-center gap-2">
                                <BillableIcon class="h-4 text-icon-default" />
                                <span>{{ timeEntry.billable ? 'Billable' : 'Non-Billable' }}</span>
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="true">Billable</SelectItem>
                            <SelectItem value="false">Non Billable</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 pt-4">
                <Field class="col-span-2 sm:col-span-3">
                    <FieldLabel>Duration</FieldLabel>
                    <div class="space-y-2 flex flex-col">
                        <DurationHumanInput
                            v-model:start="localStart"
                            v-model:end="localEnd"
                            name="Duration"></DurationHumanInput>
                        <div class="text-sm flex space-x-1">
                            <InformationCircleIcon
                                class="w-4 shrink-0 text-text-quaternary"></InformationCircleIcon>
                            <span class="text-text-secondary text-xs">
                                You can type natural language like
                                <span class="font-semibold"> 2h 30m</span>
                            </span>
                        </div>
                    </div>
                </Field>
                <Field>
                    <FieldLabel>Start</FieldLabel>
                    <div class="flex flex-col gap-2">
                        <TimePickerSimple v-model="localStart" class="w-full"></TimePickerSimple>
                        <DatePicker v-model="localStart" class="w-full" tabindex="1"></DatePicker>
                    </div>
                </Field>
                <Field>
                    <FieldLabel>End</FieldLabel>
                    <div class="flex flex-col gap-2">
                        <TimePickerSimple v-model="localEnd" class="w-full"></TimePickerSimple>
                        <DatePicker v-model="localEnd" class="w-full" tabindex="1"></DatePicker>
                    </div>
                </Field>
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
