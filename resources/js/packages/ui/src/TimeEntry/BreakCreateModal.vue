<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { Field, FieldLabel } from '../field';
import { getDayJsInstance, getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import type { CreateTimeEntryBody } from '@/packages/api/src';
import TimeRangeFields from '@/packages/ui/src/TimeEntry/TimeRangeFields.vue';
import { Coffee } from '@lucide/vue';

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    createTimeEntry: (entry: Omit<CreateTimeEntryBody, 'member_id'>) => Promise<void>;
    start?: string;
    end?: string;
}>();

function defaultStart() {
    return getDayJsInstance().utc().subtract(30, 'm').second(0).format();
}

function defaultEnd() {
    return getDayJsInstance().utc().second(0).format();
}

const note = ref('');
const localStart = ref(getLocalizedDayJs(defaultStart()).format());
const localEnd = ref(getLocalizedDayJs(defaultEnd()).format());

// Prefill start/end when the modal is opened with a given range (e.g. from the calendar)
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

const durationSeconds = computed(() =>
    getLocalizedDayJs(localEnd.value).diff(getLocalizedDayJs(localStart.value), 'second')
);

async function submit() {
    if (durationSeconds.value <= 0) return;
    saving.value = true;
    try {
        await props.createTimeEntry({
            description: note.value,
            project_id: null,
            task_id: null,
            tags: [],
            billable: false,
            type: 'break',
            start: getLocalizedDayJs(localStart.value).utc().format(),
            end: getLocalizedDayJs(localEnd.value).utc().format(),
        });
        note.value = '';
        localStart.value = getLocalizedDayJs(defaultStart()).format();
        localEnd.value = getLocalizedDayJs(defaultEnd()).format();
        show.value = false;
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex items-center space-x-2 text-amber-600 dark:text-amber-400">
                <Coffee class="w-5 h-5" />
                <span> Add break </span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4">
                <TimeRangeFields
                    v-model:start="localStart"
                    v-model:end="localEnd"
                    date-picker-size="sm"></TimeRangeFields>
                <Field>
                    <FieldLabel for="break_note">Note (optional)</FieldLabel>
                    <TextInput
                        id="break_note"
                        v-model="note"
                        placeholder="e.g. Lunch"
                        type="text"
                        class="block w-full"
                        @keydown.enter="submit" />
                </Field>
            </div>
        </template>
        <template #footer>
            <SecondaryButton tabindex="2" @click="show = false"> Cancel</SecondaryButton>
            <PrimaryButton
                tabindex="2"
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving || durationSeconds <= 0"
                @click="submit">
                Add Break
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
