<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { useOrganizationStore } from '@/utils/useOrganization';

const props = defineProps<{
    team: { id: string };
}>();

const confirmingTeamDeletion = ref(false);
const processing = ref(false);
const organizationStore = useOrganizationStore();

const confirmTeamDeletion = () => {
    confirmingTeamDeletion.value = true;
};

const deleteTeam = async () => {
    processing.value = true;
    try {
        await organizationStore.deleteOrganization(props.team.id);
        // The backend reassigns the user's current organization after deletion,
        // so flush the prefetch cache and reload into the dashboard.
        router.flushAll();
        router.visit(route('dashboard'));
    } catch {
        // Request errors are surfaced as notifications by the store.
        processing.value = false;
    }
};
</script>

<template>
    <ActionSection>
        <template #title> Delete Organization </template>

        <template #description> Permanently delete this organization. </template>

        <template #content>
            <div class="max-w-xl text-sm text-text-secondary">
                Once a organization is deleted, all of its resources and data will be permanently
                deleted. Before deleting this organization, please download any data or information
                regarding this organization that you wish to retain.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmTeamDeletion"> Delete Organization </DangerButton>
            </div>

            <!-- Delete Organization Confirmation Modal -->
            <ConfirmationModal
                :show="confirmingTeamDeletion"
                @close="confirmingTeamDeletion = false">
                <template #title> Delete Organization </template>

                <template #content>
                    Are you sure you want to delete this organization? Once a organization is
                    deleted, all of its resources and data will be permanently deleted.
                </template>

                <template #footer>
                    <SecondaryButton @click="confirmingTeamDeletion = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing"
                        @click="deleteTeam">
                        Delete Organization
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </template>
    </ActionSection>
</template>
