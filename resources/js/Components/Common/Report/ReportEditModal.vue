<script setup lang="ts">
import TextInput from '../../../packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '../../../packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref, watch } from 'vue';
import PrimaryButton from '../../../packages/ui/src/Buttons/PrimaryButton.vue';
import InputLabel from '../../../packages/ui/src/Input/InputLabel.vue';
import type { UpdateReportBody } from '@/packages/api/src';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { Checkbox } from '@/packages/ui/src';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import { useNotificationsStore } from '@/utils/notification';
import type { Report } from '@/packages/api/src';

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

const { handleApiRequestNotifications } = useNotificationsStore();

async function submit() {
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
                <div class="w-full">
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="report.name" class="mt-1.5 w-full"></TextInput>
                </div>
                <div>
                    <InputLabel for="description" value="Description" />
                    <TextInput
                        id="description"
                        v-model="report.description"
                        class="mt-1.5 w-full"></TextInput>
                </div>
                <InputLabel value="Visibility" />
                <div class="flex items-center space-x-12">
                    <div class="flex items-center space-x-2 px-2 py-3">
                        <Checkbox id="is_public" v-model:checked="report.is_public"></Checkbox>
                        <InputLabel for="is_public" value="Public" />
                    </div>
                    <div v-if="report.is_public" class="flex items-center space-x-4">
                        <InputLabel for="public_until" value="Expires at" />
                        <DatePicker id="public_until"></DatePicker>
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
                Update Report
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
