<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteTeamForm from '@/Pages/Teams/Partials/DeleteTeamForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateTeamNameForm from '@/Pages/Teams/Partials/UpdateTeamNameForm.vue';
import type { Organization } from '@/types/models';
import type { Permissions, Role } from '@/types/jetstream';
import { canUpdateOrganization } from '@/utils/permissions';
import OrganizationBillableRate from '@/Pages/Teams/Partials/OrganizationBillableRate.vue';

defineProps<{
    team: Organization;
    availableRoles: Role[];
    permissions: Permissions;
}>();
</script>

<template>
    <AppLayout title="Organization Settings">
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">
                Organization Settings
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <UpdateTeamNameForm :team="team" :permissions="permissions" />

                <SectionBorder />
                <OrganizationBillableRate
                    v-if="canUpdateOrganization()"
                    :team="team" />
                <SectionBorder />

                <template
                    v-if="permissions.canDeleteTeam && !team.personal_team">
                    <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
