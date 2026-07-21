<script setup lang="ts">
import ActionSection from '@/Components/ActionSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    connected: boolean;
    googleEmail: string | null;
}>();

const disconnectForm = useForm({});

function disconnect() {
    disconnectForm.delete(route('google-calendar.disconnect'));
}
</script>

<template>
    <ActionSection>
        <template #title> Google Calendar </template>

        <template #description>
            Connect your Google Calendar to see your meetings in the calendar view and turn them
            into time entries with one click.
        </template>

        <template #content>
            <div v-if="connected" class="flex items-center justify-between">
                <div class="text-sm text-text-secondary">
                    Connected as
                    <span class="font-medium text-text-primary">{{ googleEmail }}</span>
                </div>
                <DangerButton
                    :class="{ 'opacity-25': disconnectForm.processing }"
                    :disabled="disconnectForm.processing"
                    @click="disconnect">
                    Disconnect
                </DangerButton>
            </div>
            <div v-else class="flex items-center justify-between">
                <div class="text-sm text-text-secondary">No Google Calendar connected.</div>
                <a href="/settings/google-calendar/connect">
                    <PrimaryButton type="button"> Connect Google Calendar </PrimaryButton>
                </a>
            </div>
        </template>
    </ActionSection>
</template>
