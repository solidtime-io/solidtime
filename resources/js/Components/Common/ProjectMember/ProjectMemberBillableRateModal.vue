<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { formatCents } from '../../../utils/money';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/solid';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

defineProps<{
    newBillableRate?: number | null;
    memberName: string;
}>();

const emit = defineEmits<{
    submit: [billable_rate_update_time_entries: boolean];
}>();
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex justify-center">
                <span> Update Project Member Billable Rate </span>
            </div>
        </template>
        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <p class="py-1 text-center">
                        The billable rate of {{ memberName }} will be updated to
                        <strong>{{
                            newBillableRate
                                ? formatCents(newBillableRate)
                                : ' the default rate of the organization'
                        }}</strong
                        >.
                    </p>
                    <p class="py-1 text-center font-semibold max-w-md mx-auto">
                        Do you want to update all existing time entries, where
                        the project member billable rate applies as well?
                    </p>

                    <div class="space-x-3 pt-5 pb-2 flex justify-center">
                        <PrimaryButton
                            :class="{ 'opacity-25': saving }"
                            :disabled="saving"
                            @click="emit('submit', true)">
                            Yes, update existing time entries
                        </PrimaryButton>
                        <PrimaryButton
                            :class="{ 'opacity-25': saving }"
                            :disabled="saving"
                            @click="emit('submit', false)">
                            No, only for new time entries
                        </PrimaryButton>
                    </div>
                    <p class="text-center pt-3 pb-1">
                        Learn more about the
                        <a
                            target="_blank"
                            href="https://docs.solidtime.io/user-guide/billable-rates"
                            class="text-blue-400 hover:text-blue-500 transition"
                            >billable rate logic
                            <ArrowTopRightOnSquareIcon
                                class="w-4 -mt-0.5 inline-block"></ArrowTopRightOnSquareIcon
                        ></a>
                    </p>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
