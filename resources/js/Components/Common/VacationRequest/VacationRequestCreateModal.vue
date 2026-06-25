<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import { useVacationRequestMutations, type VacationRequestType } from '@/utils/useVacationRequests';
import { canManageVacationRequests } from '@/utils/permissions';

const show = defineModel('show', { default: false });
const emit = defineEmits<{ created: [] }>();

const { create } = useVacationRequestMutations();

const typeOptions: { value: VacationRequestType; label: string }[] = [
    { value: 'regular_vacation', label: 'Regular vacation from quota' },
    { value: 'sick_day', label: 'Sick day' },
    { value: 'work_outside', label: 'Work outside office' },
    { value: 'special', label: 'Special leave' },
];

const form = ref({
    type: 'regular_vacation' as VacationRequestType,
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
    half_day: false,
    private_note: '',
    public_note: '',
    showPublicNote: false,
    createAnother: false,
    member_id: null as string | null,
});

const errors = ref<Record<string, string>>({});

const daysCount = computed(() => {
    if (form.value.half_day) return 1;
    const start = new Date(form.value.start_date);
    const end = form.value.end_date ? new Date(form.value.end_date) : start;
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return 1;

    let days = 0;
    const cur = new Date(start);
    while (cur <= end) {
        const day = cur.getDay();
        if (day !== 0 && day !== 6) days++;
        cur.setDate(cur.getDate() + 1);
    }
    return Math.max(1, days);
});

watch(
    () => form.value.start_date,
    (val) => {
        if (!form.value.end_date || form.value.end_date < val) {
            form.value.end_date = val;
        }
    }
);

const canManage = computed(() => canManageVacationRequests());

function resetForm() {
    form.value = {
        type: 'regular_vacation',
        start_date: new Date().toISOString().slice(0, 10),
        end_date: '',
        half_day: false,
        private_note: '',
        public_note: '',
        showPublicNote: false,
        createAnother: false,
        member_id: null,
    };
    errors.value = {};
}

async function submit() {
    errors.value = {};

    if (!form.value.start_date) {
        errors.value.start_date = 'Start date is required';
        return;
    }

    await create.mutateAsync({
        type: form.value.type,
        start_date: form.value.start_date,
        end_date: form.value.end_date || form.value.start_date,
        half_day: form.value.half_day,
        private_note: form.value.private_note || null,
        public_note: form.value.showPublicNote ? form.value.public_note || null : null,
        member_id: canManage.value ? form.value.member_id : null,
    });

    emit('created');

    if (form.value.createAnother) {
        resetForm();
    } else {
        show.value = false;
        resetForm();
    }
}

function close() {
    show.value = false;
    resetForm();
}
</script>

<template>
    <DialogModal :show="show" max-width="lg" @close="close">
        <template #title>Record Absence</template>
        <template #content>
            <div class="space-y-4">
                <!-- Type -->
                <Field>
                    <FieldLabel>Type</FieldLabel>
                    <select
                        v-model="form.type"
                        class="w-full rounded-md border border-input-border bg-input-background text-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </Field>

                <!-- Date range -->
                <div class="grid grid-cols-2 gap-3">
                    <Field>
                        <FieldLabel>From / On</FieldLabel>
                        <TextInput
                            v-model="form.start_date"
                            type="date"
                            class="w-full" />
                        <FieldError v-if="errors.start_date">{{ errors.start_date }}</FieldError>
                    </Field>
                    <Field>
                        <FieldLabel>To (optional)</FieldLabel>
                        <TextInput
                            v-model="form.end_date"
                            type="date"
                            :min="form.start_date"
                            class="w-full" />
                    </Field>
                </div>

                <!-- Half day -->
                <Field>
                    <label class="flex items-center gap-2 text-sm text-text-primary cursor-pointer">
                        <input
                            v-model="form.half_day"
                            type="checkbox"
                            class="rounded border-input-border" />
                        Half day
                    </label>
                </Field>

                <!-- Days count -->
                <Field>
                    <FieldLabel>Number of days</FieldLabel>
                    <TextInput
                        :model-value="String(daysCount)"
                        type="text"
                        readonly
                        class="w-full bg-input-background/50 text-text-secondary cursor-default" />
                </Field>

                <!-- Private note -->
                <Field>
                    <FieldLabel>Private Note</FieldLabel>
                    <textarea
                        v-model="form.private_note"
                        rows="3"
                        maxlength="512"
                        placeholder="This note is only visible to managers and members with appropriate permissions."
                        class="w-full rounded-md border border-input-border bg-input-background text-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none placeholder-text-quaternary" />
                    <div class="text-xs text-text-quaternary text-right">
                        {{ form.private_note.length }}/512
                    </div>
                </Field>

                <!-- Public note toggle -->
                <button
                    v-if="!form.showPublicNote"
                    type="button"
                    class="text-sm text-blue-500 hover:underline flex items-center gap-1"
                    @click="form.showPublicNote = true">
                    <span class="text-base leading-none">+</span> Add public note
                </button>

                <!-- Public note -->
                <Field v-if="form.showPublicNote">
                    <FieldLabel>Public Note</FieldLabel>
                    <textarea
                        v-model="form.public_note"
                        rows="3"
                        maxlength="512"
                        class="w-full rounded-md border border-input-border bg-input-background text-text-primary px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none" />
                    <div class="text-xs text-text-quaternary text-right">
                        {{ form.public_note.length }}/512
                    </div>
                </Field>
            </div>
        </template>
        <template #footer>
            <div class="flex items-center justify-between w-full">
                <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer">
                    <input
                        v-model="form.createAnother"
                        type="checkbox"
                        class="rounded border-input-border" />
                    Create another
                </label>
                <div class="flex gap-2">
                    <SecondaryButton @click="close">Cancel</SecondaryButton>
                    <PrimaryButton :loading="create.isPending.value" @click="submit"
                        >Save</PrimaryButton
                    >
                </div>
            </div>
        </template>
    </DialogModal>
</template>
