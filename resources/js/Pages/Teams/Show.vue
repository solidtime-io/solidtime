<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteTeamForm from '@/Pages/Teams/Partials/DeleteTeamForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateTeamNameForm from '@/Pages/Teams/Partials/UpdateTeamNameForm.vue';
import type { Organization } from '@/types/models';
import type { Permissions, Role } from '@/types/jetstream';
import { canUpdateOrganization } from '@/utils/permissions';
import OrganizationBillableRate from '@/Pages/Teams/Partials/OrganizationBillableRate.vue';
import OrganizationFormatSettings from '@/Pages/Teams/Partials/OrganizationFormatSettings.vue';
import OrganizationTimeEntrySettings from '@/Pages/Teams/Partials/OrganizationTimeEntrySettings.vue';
import { onMounted, ref } from 'vue';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';

defineProps<{
    team: Organization;
    availableRoles: Role[];
    permissions: Permissions;
}>();

const loading = ref(true);
const orgStore = useOrganizationStore();
const { organization } = storeToRefs(orgStore);

onMounted(async () => {
    await orgStore.fetchOrganization();
    loading.value = false;
});
</script>

<template>
    <AppLayout title="Organization Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-text-primary leading-tight">
                Organization Settings
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div v-if="loading || !organization" class="py-16 text-center text-text-secondary">
                    Loading organization settings...
                </div>
                <template v-else>
                    <UpdateTeamNameForm :team="team" :permissions="permissions" />

                    <SectionBorder />
                    <OrganizationBillableRate v-if="canUpdateOrganization()" :team="team" />
                    <SectionBorder />

                    <OrganizationFormatSettings v-if="canUpdateOrganization()" :team="team" />
                    <SectionBorder />

                    <OrganizationTimeEntrySettings v-if="canUpdateOrganization()" />
                    <SectionBorder />

                    <template v-if="permissions.canDeleteTeam && !team.personal_team">
                        <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
                    </template>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
