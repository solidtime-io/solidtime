<script setup lang="ts">
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import type { Role } from '@/types/jetstream';
import { Link, useForm } from '@inertiajs/vue3';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { filterRoles } from '@/utils/roles';
import { hasActiveSubscription, isBillingActivated } from '@/utils/billing';
import { CreditCardIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import { canUpdateOrganization } from '@/utils/permissions';
import { api } from '@/utils/api';
import type { MemberRole } from '@/utils/api';
import { z } from 'zod';
import { useNotificationsStore } from '@/utils/notification';

const show = defineModel('show', { default: false });
const saving = ref(false);

defineProps<{
    availableRoles: Role[];
}>();

const errors = ref({
    email: '',
    role: '',
});

const addTeamMemberForm = useForm({
    email: '',
    role: null as string | null,
});

const emit = defineEmits(['close']);
const { handleApiRequestNotifications } = useNotificationsStore();

async function submit() {
    if (addTeamMemberForm.role === null || addTeamMemberForm.email === '') {
        errors.value.email = z
            .string()
            .email()
            .safeParse(addTeamMemberForm.email).success
            ? ''
            : 'Please enter a valid email address';
        errors.value.role =
            addTeamMemberForm.role === null ? 'Please select a role' : '';
        return;
    }

    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        await handleApiRequestNotifications(
            () =>
                api.invite(
                    {
                        email: addTeamMemberForm.email,
                        role: addTeamMemberForm.role as MemberRole,
                    },
                    {
                        params: {
                            organization: organizationId,
                        },
                    }
                ),
            'Member invited',
            'Failed to invite member',
            () => {
                addTeamMemberForm.reset();
                emit('close');
                show.value = false;
            }
        );
    }
}

const clientNameInput = ref<HTMLInputElement | null>(null);
useFocus(clientNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Invite Member </span>
            </div>
        </template>

        <template #content>
            <div v-if="isBillingActivated() && !hasActiveSubscription()">
                <div
                    class="rounded-full flex items-center justify-center w-20 h-20 mx-auto border border-border-tertiary bg-secondary">
                    <UserGroupIcon class="w-12"></UserGroupIcon>
                </div>
                <div class="max-w-sm text-center mx-auto py-4 text-base">
                    <p class="py-1">
                        The Free plan is <strong>limited to one member</strong>
                    </p>
                    <p class="py-1">
                        To add new team members to your organization you,
                        <strong>please upgrade to a paid plan</strong>.
                    </p>

                    <Link href="/billing">
                        <PrimaryButton
                            type="button"
                            class="mt-6"
                            v-if="
                                isBillingActivated() && canUpdateOrganization()
                            ">
                            <CreditCardIcon class="w-5 h-5 me-2" />
                            Go to Billing
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
            <div v-else class="space-y-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        name="email"
                        ref="memberEmailInput"
                        v-model="addTeamMemberForm.email"
                        type="text"
                        placeholder="Member Email"
                        @keydown.enter="submit"
                        class="mt-1 block w-full"
                        required
                        autocomplete="memberName" />
                    <InputError :message="errors.email" class="mt-2" />
                </div>

                <div v-if="availableRoles.length > 0">
                    <InputLabel for="roles" value="Role" />
                    <InputError :message="errors.role" class="mt-2" />

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
                                'rounded-b-none':
                                    i != Object.keys(availableRoles).length - 1,
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
                                        class="text-sm text-white"
                                        :class="{
                                            'font-semibold':
                                                addTeamMemberForm.role ==
                                                role.key,
                                        }">
                                        {{ role.name }}
                                    </div>

                                    <svg
                                        v-if="
                                            addTeamMemberForm.role == role.key
                                        "
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
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>
            <PrimaryButton
                v-if="!isBillingActivated() || hasActiveSubscription()"
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Invite Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
