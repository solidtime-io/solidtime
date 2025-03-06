<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import {api, type Member} from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import MemberCombobox from "@/Components/Common/Member/MemberCombobox.vue";
import {UserIcon, ArrowRightIcon} from "@heroicons/vue/24/solid";
import {Badge} from "@/packages/ui/src";
import { useMutation } from '@tanstack/vue-query';
import {getCurrentOrganizationId} from "@/utils/useUser";
import {useNotificationsStore} from "@/utils/notification";
const { handleApiRequestNotifications, addNotification } = useNotificationsStore();

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    member: Member;
}>();

const newMember = ref<string>('');

const mergeMember = useMutation({
    mutationFn: async (newMemberId: string) => {
        const organizationId = getCurrentOrganizationId();
        if (organizationId === null) {
            throw new Error('No current organization id - create report');
        }
        return await api.mergeMember({
            memberId: newMemberId,
        }, {
            params: {
                organization: organizationId,
                member: props.member.id
            },
        });
    },
});

async function submit() {
    const newMemberId = newMember.value;
    if(newMemberId !== ''){
        saving.value = true;
        await handleApiRequestNotifications(
            () =>
                mergeMember.mutateAsync(newMemberId),
            'Members successfully merged!',
            'There was an error merging the members.',
            () => {
                show.value = false;
            }
        );
    }
    else{
        addNotification(
            'error',
            'Please select a member to merge into.',
        );
    }

}

</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Merge Member </span>
            </div>
        </template>

        <template #content>
            <p>Merging the user <strong>{{ member.name }} </strong> into another one will transfer all time entries to the new user. <strong>This cannot be reverted!</strong></p>
            <div class="py-5 flex flex-col md:flex-row gap-6 items-center">
                <div class="flex-1">
                    <Badge class="flex w-full text-base text-left space-x-3 px-3 text-text-secondary font-normal cursor py-1.5">
                        <UserIcon class="relative z-10 w-4 text-muted"></UserIcon>
                        <div class="flex-1 font-medium truncate">
                            {{ member.name }}
                        </div>
                    </Badge>
                </div>
                <div>
                    <ArrowRightIcon class="relative z-10 w-4 text-muted"></ArrowRightIcon>
                </div>
                <div class="flex-1">
                    <MemberCombobox
                        v-model="newMember"
                    ></MemberCombobox>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit()">
                Merge Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
