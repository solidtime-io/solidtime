<script setup lang="ts">
import TextInput from '../../../packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '../../../packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '../../../packages/ui/src/Buttons/PrimaryButton.vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import type { CreateReportBody, CreateReportBodyProperties } from '@/packages/api/src';
import { useMutation } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { Checkbox } from '@/packages/ui/src';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import { useNotificationsStore } from '@/utils/notification';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { router } from '@inertiajs/vue3';

const show = defineModel('show', { default: false });
const saving = ref(false);

const createReportMutation = useMutation({
    mutationFn: async (report: CreateReportBody) => {
        const organizationId = getCurrentOrganizationId();
        if (organizationId === null) {
            throw new Error('No current organization id - create report');
        }
        return await api.createReport(report, {
            params: {
                organization: organizationId,
            },
        });
    },
});

const props = defineProps<{
    properties: CreateReportBodyProperties;
}>();

const report = ref({
    name: '',
    description: '',
    is_public: true,
    public_until: null,
});

const { handleApiRequestNotifications } = useNotificationsStore();

async function submit() {
    const publicUntil = report.value.public_until
        ? getDayJsInstance()(report.value.public_until).utc().format()
        : null;
    await handleApiRequestNotifications(
        () =>
            createReportMutation.mutateAsync({
                ...report.value,
                public_until: publicUntil,
                properties: { ...props.properties },
            }),
        'Success',
        'Error',
        () => {
            report.value = {
                name: '',
                description: '',
                is_public: false,
                public_until: null,
            };
            show.value = false;
            router.visit(route('reporting.shared'));
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
                        <Field v-if="report.is_public" class="flex-row items-center space-x-4">
                            <div>
                                <FieldLabel for="public_until">Expires at</FieldLabel>
                                <div class="text-text-tertiary font-medium">(optional)</div>
                            </div>
                            <DatePicker v-model="report.public_until"></DatePicker>
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
                Create Report
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
