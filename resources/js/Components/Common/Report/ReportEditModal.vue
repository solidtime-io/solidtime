<script setup lang="ts">
import TextInput from '../../../packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '../../../packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref, watch } from 'vue';
import PrimaryButton from '../../../packages/ui/src/Buttons/PrimaryButton.vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import type { UpdateReportBody } from '@/packages/api/src';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { Checkbox } from '@/packages/ui/src';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import { useNotificationsStore } from '@/utils/notification';
import type { Report } from '@/packages/api/src';
import { getDayJsInstance, getLocalizedDayJs } from '@/packages/ui/src/utils/time';

const show = defineModel('show', { default: false });
const saving = ref(false);
const queryClient = useQueryClient();

const updateReportMutation = useMutation({
    mutationFn: async (report: UpdateReportBody) => {
        const organizationId = getCurrentOrganizationId();
        if (organizationId === null) {
            throw new Error('No current organization id - update report');
        }
        return await api.updateReport(report, {
            params: {
                organization: organizationId,
                report: props.originalReport.id,
            },
        });
    },
    onSuccess: () => {
        queryClient.invalidateQueries({
            queryKey: ['reports'],
        });
    },
});

const props = defineProps<{
    originalReport: Report;
}>();

const report = ref<UpdateReportBody>({
    name: props.originalReport.name,
    description: props.originalReport.description,
    is_public: props.originalReport.is_public,
    public_until: props.originalReport.public_until,
});

watch(
    () => props.originalReport,
    () => {
        report.value = {
            name: props.originalReport.name,
            description: props.originalReport.description,
            is_public: props.originalReport.is_public,
            public_until: props.originalReport.public_until,
        };
    }
);

// Intermediate local variable for DatePicker (converts between UTC and localized)
const localPublicUntil = computed({
    get: () => {
        if (!report.value.public_until) return null;
        return getLocalizedDayJs(report.value.public_until).format();
    },
    set: (value: string | null) => {
        report.value.public_until = value ? getDayJsInstance()(value).utc().format() : null;
    },
});

const { handleApiRequestNotifications } = useNotificationsStore();

async function submit() {
    // public_until is already in UTC format from the computed setter
    await handleApiRequestNotifications(
        () => updateReportMutation.mutateAsync(report.value),
        'Success',
        'Error',
        () => {
            report.value = {
                name: '',
                description: '',
                is_public: false,
                public_until: null,
                properties: {},
            };
            show.value = false;
        }
    );
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Report </span>
            </div>
        </template>

        <template #content>
            <div class="items-center space-y-4 w-full">
                <Field class="w-full">
                    <FieldLabel for="name">Name</FieldLabel>
                    <TextInput id="name" v-model="report.name" class="w-full"></TextInput>
                </Field>
                <Field>
                    <FieldLabel for="description">Description</FieldLabel>
                    <TextInput
                        id="description"
                        v-model="report.description"
                        class="w-full"></TextInput>
                </Field>
                <Field>
                    <FieldLabel>Visibility</FieldLabel>
                    <div class="flex items-center space-x-12">
                        <Field orientation="horizontal" class="px-2 py-3">
                            <Checkbox id="is_public" v-model:checked="report.is_public"></Checkbox>
                            <FieldLabel for="is_public">Public</FieldLabel>
                        </Field>
                        <Field v-if="report.is_public" orientation="horizontal">
                            <FieldLabel for="public_until">Expires at</FieldLabel>
                            <DatePicker v-model="localPublicUntil"></DatePicker>
                        </Field>
                    </div>
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
                Update Report
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
