<script setup lang="ts">
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import TimeTrackerTagDropdown from '@/Components/Common/TimeTracker/TimeTrackerTagDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/Components/Common/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/Components/Common/BillableToggleButton.vue';
import { getCurrentUserId } from '@/utils/useUser';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import InputLabel from '@/Components/InputLabel.vue';
import TimePicker from '@/Components/Common/TimePicker.vue';
import DatePicker from '@/Components/Common/DatePicker.vue';
import { getDayJsInstance } from '@/utils/time';

const { createTimeEntry } = useTimeEntriesStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

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

async function submit() {
    await createTimeEntry(timeEntry.value);
    timeEntry.value = { ...timeEntryDefaultValues };
    show.value = false;
}

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });
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
                        v-model="timeEntry.description"
                        @keydown.enter="submit"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus />
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <TimeTrackerProjectTaskDropdown
                            class="mt-1"
                            size="xlarge"
                            v-model:project="timeEntry.project_id"
                            v-model:task="
                                timeEntry.task_id
                            "></TimeTrackerProjectTaskDropdown>
                    </div>
                    <div class="flex items-center space-x-2 px-4">
                        <TimeTrackerTagDropdown
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
                        <DatePicker v-model="timeEntry.start"></DatePicker>
                        <TimePicker
                            size="large"
                            v-model="timeEntry.start"></TimePicker>
                    </div>
                </div>
                <div class="flex-1">
                    <InputLabel>End</InputLabel>
                    <div class="flex items-center space-x-4 mt-1">
                        <DatePicker v-model="timeEntry.end"></DatePicker>
                        <TimePicker
                            size="large"
                            v-model="timeEntry.end"></TimePicker>
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
