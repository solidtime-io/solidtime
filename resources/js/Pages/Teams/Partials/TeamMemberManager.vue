<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';

import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { Organization, OrganizationInvitation, User } from '@/types/models';
import type { Membership, Permissions, Role } from '@/types/jetstream';
import { filterRoles } from '@/utils/roles';

type UserWithMembership = User & { membership: Membership };

const props = defineProps<{
    team: Organization;
    availableRoles: Role[];
    userPermissions: Permissions;
}>();

const users = computed(() => {
    return props.team.users as Array<UserWithMembership>;
});

const page = usePage<{
    auth: {
        user: User;
    };
}>();

const addTeamMemberForm = useForm({
    email: '',
    role: null as string | null,
});

const updateRoleForm = useForm({
    role: null as string | null,
});

const leaveTeamForm = useForm({});
const removeTeamMemberForm = useForm({});

const currentlyManagingRole = ref(false);
const managingRoleFor = ref<User | null>(null);
const confirmingLeavingTeam = ref(false);
const teamMemberBeingRemoved = ref<User | null>(null);

const addTeamMember = () => {
    addTeamMemberForm.post(route('team-members.store', props.team.id), {
        errorBag: 'addTeamMember',
        preserveScroll: true,
        onSuccess: () => addTeamMemberForm.reset(),
    });
};

const cancelTeamInvitation = (invitation: OrganizationInvitation) => {
    router.delete(route('team-invitations.destroy', invitation.id), {
        preserveScroll: true,
    });
};

const manageRole = (teamMember: User & { membership: Membership }) => {
    managingRoleFor.value = teamMember;
    updateRoleForm.role = teamMember.membership.role;
    currentlyManagingRole.value = true;
};

const updateRole = () => {
    updateRoleForm.put(
        route('team-members.update', {
            team: props.team.id,
            user: managingRoleFor.value?.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => (currentlyManagingRole.value = false),
        }
    );
};

const confirmLeavingTeam = () => {
    confirmingLeavingTeam.value = true;
};

const leaveTeam = () => {
    leaveTeamForm.delete(route('team-members.destroy', [props.team.id, page.props.auth.user.id]));
};

const confirmTeamMemberRemoval = (teamMember: User) => {
    teamMemberBeingRemoved.value = teamMember;
};

const removeTeamMember = () => {
    removeTeamMemberForm.delete(
        route('team-members.destroy', {
            team: props.team.id,
            user: teamMemberBeingRemoved.value?.id,
        }),
        {
            errorBag: 'removeTeamMember',
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (teamMemberBeingRemoved.value = null),
        }
    );
};

const displayableRole = (role: string) => {
    return props.availableRoles.find((r) => r.key === role)?.name;
};
</script>

<template>
    <div>
        <div v-if="userPermissions.canAddTeamMembers">
            <SectionBorder />

            <!-- Add Organization Member -->
            <FormSection @submitted="addTeamMember">
                <template #title> Add Organization Member</template>

                <template #description>
                    Add a new member to your organization, allowing them to collaborate with you.
                </template>

                <template #form>
                    <div class="col-span-6">
                        <div class="max-w-xl text-sm text-muted">
                            Please provide the email address of the person you would like to add to
                            this organization.
                        </div>
                    </div>

                    <!-- Member Email -->
                    <Field class="col-span-6 sm:col-span-4">
                        <FieldLabel for="email">Email</FieldLabel>
                        <TextInput
                            id="email"
                            v-model="addTeamMemberForm.email"
                            type="email"
                            class="block w-full" />
                        <FieldError v-if="addTeamMemberForm.errors.email">{{
                            addTeamMemberForm.errors.email
                        }}</FieldError>
                    </Field>

                    <!-- Role -->
                    <div v-if="availableRoles.length > 0" class="col-span-6 lg:col-span-4">
                        <FieldLabel for="roles">Role</FieldLabel>
                        <FieldError v-if="addTeamMemberForm.errors.role">{{
                            addTeamMemberForm.errors.role
                        }}</FieldError>

                        <div
                            class="relative z-0 mt-1 border border-card-border rounded-lg cursor-pointer">
                            <button
                                v-for="(role, i) in filterRoles(availableRoles)"
                                :key="role.key"
                                type="button"
                                class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :class="{
                                    'border-t border-card-border focus:border-none rounded-t-none':
                                        i > 0,
                                    'rounded-b-none': i != Object.keys(availableRoles).length - 1,
                                }"
                                @click="addTeamMemberForm.role = role.key">
                                <div
                                    :class="{
                                        'opacity-50':
                                            addTeamMemberForm.role &&
                                            addTeamMemberForm.role != role.key,
                                    }">
                                    <!-- Role Name -->
                                    <div class="flex items-center">
                                        <div
                                            class="text-sm text-text-primary"
                                            :class="{
                                                'font-semibold': addTeamMemberForm.role == role.key,
                                            }">
                                            {{ role.name }}
                                        </div>

                                        <svg
                                            v-if="addTeamMemberForm.role == role.key"
                                            class="ms-2 h-5 w-5 text-green-400"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>

                                    <!-- Role Description -->
                                    <div class="mt-2 text-xs text-muted text-start">
                                        {{ role.description }}
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>

                <template #actions>
                    <ActionMessage :on="addTeamMemberForm.recentlySuccessful" class="me-3">
                        Added.
                    </ActionMessage>

                    <PrimaryButton
                        :class="{ 'opacity-25': addTeamMemberForm.processing }"
                        :disabled="addTeamMemberForm.processing">
                        Add
                    </PrimaryButton>
                </template>
            </FormSection>
        </div>

        <div v-if="team.team_invitations.length > 0 && userPermissions.canAddTeamMembers">
            <SectionBorder />

            <!-- Organization Member Invitations -->
            <ActionSection class="mt-10 sm:mt-0">
                <template #title> Pending Organization Invitations</template>

                <template #description>
                    These people have been invited to your organization and have been sent an
                    invitation email. They may join the organization by accepting the email
                    invitation.
                </template>

                <!-- Pending Organization Member Invitation List -->
                <template #content>
                    <div class="space-y-6">
                        <div
                            v-for="invitation in team.team_invitations"
                            :key="invitation.id"
                            class="flex items-center justify-between">
                            <div class="text-muted">
                                {{ invitation.email }}
                            </div>

                            <div class="flex items-center">
                                <!-- Cancel Organization Invitation -->
                                <button
                                    v-if="userPermissions.canRemoveTeamMembers"
                                    class="cursor-pointer ms-6 text-sm text-red-500 focus:outline-none"
                                    @click="cancelTeamInvitation(invitation)">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>

        <div v-if="users.length > 0">
            <SectionBorder />

            <!-- Manage Organization Members -->
            <ActionSection class="mt-10 sm:mt-0">
                <template #title> Organization Members</template>

                <template #description>
                    All of the people that are part of this organization.
                </template>

                <!-- Organization Member List -->
                <template #content>
                    <div class="space-y-6">
                        <div
                            v-for="user in users"
                            :key="user.id"
                            class="flex items-center justify-between">
                            <div class="flex items-center">
                                <img
                                    class="w-8 h-8 rounded-full object-cover"
                                    :src="user.profile_photo_url"
                                    :alt="user.name" />
                                <div class="ms-4 text-text-primary">
                                    {{ user.name }}
                                </div>
                            </div>

                            <div class="flex items-center">
                                <!-- Manage Organization Member Role -->
                                <button
                                    v-if="
                                        userPermissions.canUpdateTeamMembers &&
                                        availableRoles.length
                                    "
                                    class="ms-2 text-sm text-gray-400 underline"
                                    @click="manageRole(user)">
                                    {{ displayableRole(user.membership.role) }}
                                </button>

                                <div
                                    v-else-if="availableRoles.length"
                                    class="ms-2 text-sm text-gray-400">
                                    {{ displayableRole(user.membership.role) }}
                                </div>

                                <!-- Leave Organization -->
                                <button
                                    v-if="page.props.auth.user.id === user.id"
                                    class="cursor-pointer ms-6 text-sm text-red-500"
                                    @click="confirmLeavingTeam">
                                    Leave
                                </button>

                                <!-- Remove Organization Member -->
                                <button
                                    v-else-if="userPermissions.canRemoveTeamMembers"
                                    class="cursor-pointer ms-6 text-sm text-red-500"
                                    @click="confirmTeamMemberRemoval(user)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>

        <!-- Role Management Modal -->
        <DialogModal :show="currentlyManagingRole" @close="currentlyManagingRole = false">
            <template #title> Manage Role</template>

            <template #content>
                <div v-if="managingRoleFor">
                    <div
                        class="relative z-0 mt-1 border border-card-border rounded-lg cursor-pointer">
                        <button
                            v-for="(role, i) in availableRoles"
                            :key="role.key"
                            type="button"
                            class="relative px-4 py-3 inline-flex w-full rounded-lg focus:z-10 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            :class="{
                                'border-t border-card-border focus:border-none rounded-t-none':
                                    i > 0,
                                'rounded-b-none': i !== Object.keys(availableRoles).length - 1,
                            }"
                            @click="updateRoleForm.role = role.key">
                            <div
                                :class="{
                                    'opacity-50':
                                        updateRoleForm.role && updateRoleForm.role !== role.key,
                                }">
                                <!-- Role Name -->
                                <div class="flex items-center">
                                    <div
                                        class="text-sm text-muted"
                                        :class="{
                                            'font-semibold': updateRoleForm.role === role.key,
                                        }">
                                        {{ role.name }}
                                    </div>

                                    <svg
                                        v-if="updateRoleForm.role == role.key"
                                        class="ms-2 h-5 w-5 text-green-400"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <!-- Role Description -->
                                <div class="mt-2 text-xs text-muted">
                                    {{ role.description }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="currentlyManagingRole = false"> Cancel </SecondaryButton>

                <PrimaryButton
                    class="ms-3"
                    :class="{ 'opacity-25': updateRoleForm.processing }"
                    :disabled="updateRoleForm.processing"
                    @click="updateRole">
                    Save
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Leave Organization Confirmation Modal -->
        <ConfirmationModal :show="confirmingLeavingTeam" @close="confirmingLeavingTeam = false">
            <template #title> Leave Organization</template>

            <template #content> Are you sure you would like to leave this organization? </template>

            <template #footer>
                <SecondaryButton @click="confirmingLeavingTeam = false"> Cancel </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': leaveTeamForm.processing }"
                    :disabled="leaveTeamForm.processing"
                    @click="leaveTeam">
                    Leave
                </DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Remove Organization Member Confirmation Modal -->
        <ConfirmationModal :show="!!teamMemberBeingRemoved" @close="teamMemberBeingRemoved = null">
            <template #title> Remove Organization Member</template>

            <template #content>
                Are you sure you would like to remove this person from the organization?
            </template>

            <template #footer>
                <SecondaryButton @click="teamMemberBeingRemoved = null"> Cancel </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': removeTeamMemberForm.processing }"
                    :disabled="removeTeamMemberForm.processing"
                    @click="removeTeamMember">
                    Remove
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
