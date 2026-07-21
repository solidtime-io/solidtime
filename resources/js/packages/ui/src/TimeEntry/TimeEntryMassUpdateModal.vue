<script setup lang="ts">
import TextInput from '../Input/TextInput.vue';
import SecondaryButton from '../Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, nextTick, ref, watch } from 'vue';
import PrimaryButton from '../Buttons/PrimaryButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { Field, FieldLabel } from '../field';
import {
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
    type Client,
    type TimeEntry,
    type UpdateMultipleTimeEntriesChangeset,
} from '@/packages/api/src';
import { Checkbox } from '@/packages/ui/src';
import { TagIcon, ExclamationTriangleIcon } from '@heroicons/vue/20/solid';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '..';
import { Button } from '@/packages/ui/src/Buttons';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import type { Tag, Task } from '@/packages/api/src';

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    timeEntries: TimeEntry[];
    projects: Project[];
    tasks: Task[];
    clients: Client[];
    tags: Tag[];
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    createTag: (name: string) => Promise<Tag | undefined>;
    updateTimeEntries: (changeset: UpdateMultipleTimeEntriesChangeset) => Promise<void>;
    currency: string;
    organizationBillableRate: number | null;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const descriptionInput = ref<HTMLInputElement | null>(null);

watch(show, (value) => {
    if (value) {
        nextTick(() => {
            descriptionInput.value?.focus();
        });
    }
});

const description = ref<string>('');
const taskId = ref<string | null | undefined>(undefined);
const projectId = ref<string | null>(null);
const billable = ref<boolean | undefined>(undefined);
const selectedTags = ref<string[]>([]);

const timeEntryBillable = computed({
    get: () => {
        if (billable.value === undefined) {
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
    saving.value = true;
    const timeEntryUpdatesBody = {} as UpdateMultipleTimeEntriesChangeset;
    if (description.value && description.value !== '') {
        timeEntryUpdatesBody.description = description.value;
    }
    if (projectId.value !== null) {
        if (projectId.value === '') {
            // "No Project" is selected
            timeEntryUpdatesBody.project_id = null;
        } else {
            timeEntryUpdatesBody.project_id = projectId.value;
        }
        timeEntryUpdatesBody.task_id = null;
        if (taskId.value !== undefined) {
            timeEntryUpdatesBody.task_id = taskId.value;
        }
    }

    if (billable.value !== undefined) {
        timeEntryUpdatesBody.billable = billable.value;
    }
    if (selectedTags.value.length > 0) {
        timeEntryUpdatesBody.tags = selectedTags.value;
    }
    if (removeAllTags.value) {
        timeEntryUpdatesBody.tags = [];
    }

    try {
        await props.updateTimeEntries({ ...timeEntryUpdatesBody });

        show.value = false;
        emit('submit');
        description.value = '';
        projectId.value = null;
        taskId.value = undefined;
        selectedTags.value = [];
        billable.value = undefined;
        saving.value = false;
        removeAllTags.value = false;
    } catch {
        saving.value = false;
    }
}
const removeAllTags = ref(false);
watch(removeAllTags, () => {
    if (removeAllTags.value) {
        selectedTags.value = [];
    }
});

const selectedBreaksCount = computed(
    () => props.timeEntries.filter((entry) => entry.type === 'break').length
);

// Mirrors the server-side skip in TimeEntryController::updateMultiple: a break
// entry is skipped entirely when the changeset assigns a project, makes it
// billable, or adds tags (clearing tags via removeAllTags is fine).
const showBreakWarning = computed(
    () =>
        selectedBreaksCount.value > 0 &&
        ((projectId.value !== null && projectId.value !== '') ||
            billable.value === true ||
            selectedTags.value.length > 0)
);
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
                <div
                    v-if="showBreakWarning"
                    data-testid="mass_update_break_warning"
                    class="flex items-start space-x-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-600 dark:text-amber-400">
                    <ExclamationTriangleIcon class="w-4 h-4 mt-0.5 shrink-0" />
                    <span>
                        {{ selectedBreaksCount }}
                        {{ selectedBreaksCount === 1 ? 'break is' : 'breaks are' }} selected —
                        breaks can not have a project or tags, or be billable, so
                        {{ selectedBreaksCount === 1 ? 'this entry' : 'these entries' }} will be
                        skipped entirely and none of the changes (including the description) will be
                        applied to {{ selectedBreaksCount === 1 ? 'it' : 'them' }}.
                    </span>
                </div>
                <Field>
                    <FieldLabel for="description">Description</FieldLabel>
                    <TextInput
                        id="description"
                        ref="descriptionInput"
                        v-model="description"
                        type="text"
                        class="block w-full"
                        @keydown.enter="submit" />
                </Field>
                <Field>
                    <FieldLabel for="project">Project</FieldLabel>
                    <TimeTrackerProjectTaskDropdown
                        v-model:project="projectId"
                        v-model:task="taskId"
                        variant="input"
                        align="start"
                        size="default"
                        :clients
                        :create-project
                        :create-client
                        :currency="currency"
                        :organization-billable-rate="organizationBillableRate"
                        :can-create-project
                        empty-placeholder="Select project..."
                        allow-reset
                        :enable-estimated-time
                        :projects="projects"
                        :tasks="tasks"></TimeTrackerProjectTaskDropdown>
                </Field>
                <Field>
                    <FieldLabel>Tag</FieldLabel>
                    <div class="flex space-x-5">
                        <TagDropdown
                            v-model="selectedTags"
                            :create-tag
                            :tags="tags"
                            :show-no-tag-option="false">
                            <template #trigger>
                                <Button variant="input" :disabled="removeAllTags">
                                    <TagIcon class="h-4 text-icon-default" />
                                    <span v-if="selectedTags.length > 0">
                                        Set {{ selectedTags.length }} tags
                                    </span>
                                    <span v-else>Select Tags...</span>
                                </Button>
                            </template>
                        </TagDropdown>
                        <Field orientation="horizontal">
                            <Checkbox id="no_tags" v-model:checked="removeAllTags"></Checkbox>
                            <FieldLabel for="no_tags">Remove all tags</FieldLabel>
                        </Field>
                    </div>
                </Field>
                <Field>
                    <FieldLabel>Billable</FieldLabel>
                    <Select v-model="timeEntryBillable">
                        <SelectTrigger>
                            <SelectValue>
                                <span v-if="billable === undefined">Set billable status</span>
                                <span v-else-if="billable === true">Billable</span>
                                <span v-else>Non Billable</span>
                            </SelectValue>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="do-not-update">
                                Keep current billable status
                            </SelectItem>
                            <SelectItem value="billable">Billable</SelectItem>
                            <SelectItem value="non-billable">Non Billable</SelectItem>
                        </SelectContent>
                    </Select>
                </Field>
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
