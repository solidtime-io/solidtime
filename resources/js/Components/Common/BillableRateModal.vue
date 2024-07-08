<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/solid';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

const emit = defineEmits<{
    submit: [billable_rate_update_time_entries: boolean];
}>();

defineProps<{
    title: string;
}>();
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex justify-center">
                <span> {{ title }} </span>
            </div>
        </template>
        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <slot></slot>
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
