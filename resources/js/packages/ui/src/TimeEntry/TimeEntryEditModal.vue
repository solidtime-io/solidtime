<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, nextTick, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { Field, FieldLabel } from '../field';
import { TagIcon } from '@heroicons/vue/20/solid';
import { getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Client,
    TimeEntry,
} from '@/packages/api/src';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '..';
import { Button } from '@/packages/ui/src/Buttons';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import DurationHumanInput from '@/packages/ui/src/Input/DurationHumanInput.vue';

import { InformationCircleIcon } from '@heroicons/vue/20/solid';
import { Coffee } from '@lucide/vue';
import type { Tag, Task } from '@/packages/api/src';
import TimePickerSimple from '@/packages/ui/src/Input/TimePickerSimple.vue';
import { useBreaksEnabled } from '@/packages/ui/src/utils/useBreaksEnabled';

// Breaks may have been disabled after this entry was created, so an existing break can still be
// edited (and converted back), but a work entry may only offer the break option when enabled.
const breaksEnabled = useBreaksEnabled();

const show = defineModel('show', { default: false });
const saving = ref(false);
const deleting = ref(false);

const props = defineProps<{
    timeEntry: TimeEntry | null;
    enableEstimatedTime: boolean;
    updateTimeEntry: (entry: TimeEntry) => Promise<void>;
    deleteTimeEntry: (timeEntryId: string) => Promise<void>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
    tags: Tag[];
    projects: Project[];
    tasks: Task[];
    clients: Client[];
    currency: string;
    organizationBillableRate: number | null;
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

const editableTimeEntry = ref<TimeEntry | null>(null);

watch(
    () => props.timeEntry,
    (newTimeEntry) => {
        if (newTimeEntry) {
            editableTimeEntry.value = { ...newTimeEntry };
        }
    },
    { immediate: true }
);

watch(
    () => editableTimeEntry.value?.project_id,
    (value, oldValue) => {
        if (oldValue !== undefined && value !== oldValue && editableTimeEntry.value) {
            const project = props.projects.find((p) => p.id === value);
            if (project) {
                editableTimeEntry.value.billable = project.is_billable;
            }
        }
    }
);

const localStart = computed({
    get: () =>
        editableTimeEntry.value ? getLocalizedDayJs(editableTimeEntry.value.start).format() : '',
    set: (value: string) => {
        if (editableTimeEntry.value) {
            editableTimeEntry.value.start = getLocalizedDayJs(value).utc().format();
            if (getLocalizedDayJs(localEnd.value).isBefore(getLocalizedDayJs(value))) {
                localEnd.value = value;
            }
        }
    },
});

const localEnd = computed({
    get: () =>
        editableTimeEntry.value ? getLocalizedDayJs(editableTimeEntry.value.end).format() : '',
    set: (value: string) => {
        if (editableTimeEntry.value) {
            editableTimeEntry.value.end = getLocalizedDayJs(value).utc().format();
        }
    },
});

async function submit() {
    if (editableTimeEntry.value) {
        saving.value = true;
        try {
            await props.updateTimeEntry(editableTimeEntry.value);
            show.value = false;
        } finally {
            saving.value = false;
        }
    }
}

async function deleteEntry() {
    if (editableTimeEntry.value) {
        deleting.value = true;
        try {
            await props.deleteTimeEntry(editableTimeEntry.value.id);
            show.value = false;
        } finally {
            deleting.value = false;
        }
    }
}

const billableProxy = computed({
    get: () =>
        editableTimeEntry.value ? (editableTimeEntry.value.billable ? 'true' : 'false') : 'false',
    set: (value: string) => {
        if (editableTimeEntry.value) {
            editableTimeEntry.value.billable = value === 'true';
        }
    },
});

const isBreak = computed(() => editableTimeEntry.value?.type === 'break');

const typeProxy = computed({
    get: () => editableTimeEntry.value?.type ?? 'work',
    set: (value: string) => {
        if (editableTimeEntry.value) {
            editableTimeEntry.value.type = value as TimeEntry['type'];
            if (value === 'break') {
                // Breaks can not be billable, have tags or belong to a project/task
                editableTimeEntry.value.project_id = null;
                editableTimeEntry.value.task_id = null;
                editableTimeEntry.value.billable = false;
                editableTimeEntry.value.tags = [];
            }
        }
    },
});
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Edit time entry </span>
            </div>
        </template>

        <template #content>
            <div v-if="editableTimeEntry" class="space-y-4">
                <div class="sm:flex items-end space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="flex-1">
                        <TextInput
                            id="description"
                            ref="description"
                            v-model="editableTimeEntry.description"
                            placeholder="What did you work on?"
                            type="text"
                            class="mt-1 block w-full"
                            @keydown.enter="submit" />
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                    <div v-if="!isBreak" class="flex-1 min-w-0">
                        <TimeTrackerProjectTaskDropdown
                            v-model:project="editableTimeEntry.project_id"
                            v-model:task="editableTimeEntry.task_id"
                            variant="input"
                            size="default"
                            :clients
                            :create-project
                            :create-client
                            :can-create-project="canCreateProject"
                            :currency="currency"
                            :organization-billable-rate="organizationBillableRate"
                            :projects="projects"
                            :tasks="tasks"
                            :enable-estimated-time="enableEstimatedTime" />
                    </div>
                    <div v-else class="flex-1 min-w-0"></div>
                    <div class="flex items-center gap-2 shrink-0">
                        <Select v-if="breaksEnabled || isBreak" v-model="typeProxy">
                            <SelectTrigger :show-chevron="false">
                                <SelectValue class="flex items-center gap-2">
                                    <Coffee
                                        class="h-4 w-4"
                                        :class="isBreak ? 'text-amber-500' : 'text-icon-default'" />
                                    <span>{{ isBreak ? 'Break' : 'Work time' }}</span>
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="work">Work time</SelectItem>
                                <SelectItem value="break">Break</SelectItem>
                            </SelectContent>
                        </Select>
                        <TagDropdown
                            v-if="!isBreak"
                            v-model="editableTimeEntry.tags"
                            :create-tag
                            :tags="tags"
                            :show-no-tag-option="false">
                            <template #trigger>
                                <Button variant="input">
                                    <TagIcon class="h-4 text-icon-default" />
                                    <span>{{
                                        editableTimeEntry.tags.length === 0
                                            ? 'Tags'
                                            : `${editableTimeEntry.tags.length} Tag${editableTimeEntry.tags.length > 1 ? 's' : ''}`
                                    }}</span>
                                </Button>
                            </template>
                        </TagDropdown>
                        <Select v-if="!isBreak" v-model="billableProxy">
                            <SelectTrigger :show-chevron="false">
                                <SelectValue class="flex items-center gap-2">
                                    <BillableIcon class="h-4 text-icon-default" />
                                    <span>{{
                                        editableTimeEntry.billable ? 'Billable' : 'Non-Billable'
                                    }}</span>
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
                            <TimePickerSimple
                                v-model="localStart"
                                class="w-full"></TimePickerSimple>
                            <DatePicker
                                v-model="localStart"
                                class="w-full"
                                tabindex="1"></DatePicker>
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
            </div>
        </template>
        <template #footer>
            <div class="flex justify-between w-full">
                <SecondaryButton
                    tabindex="2"
                    class="bg-red-600 hover:bg-red-700 text-white border-red-600 hover:border-red-700"
                    :disabled="deleting || saving"
                    @click="deleteEntry">
                    {{ deleting ? 'Deleting...' : 'Delete' }}
                </SecondaryButton>
                <div class="flex space-x-3">
                    <SecondaryButton tabindex="2" @click="show = false"> Cancel</SecondaryButton>
                    <PrimaryButton
                        tabindex="2"
                        :class="{ 'opacity-25': saving }"
                        :disabled="saving || deleting"
                        @click="submit">
                        {{ saving ? 'Updating...' : 'Update Time Entry' }}
                    </PrimaryButton>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<style scoped></style>
