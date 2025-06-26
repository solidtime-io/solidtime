<script setup lang="ts">
import type { Member } from '@/packages/api/src';
import { api } from '@/packages/api/src';
import { useForm } from '@tanstack/vue-form';
import { useMutation } from '@tanstack/vue-query';
import Modal from '@/packages/ui/src/Modal.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import { useNotificationsStore } from '@/utils/notification';
import { getCurrentOrganizationId } from '@/utils/useUser';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';    
import InputError from '@/packages/ui/src/Input/InputError.vue';
import { useMembersStore } from '@/utils/useMembers';

const props = defineProps<{
    show: boolean;
    member: Member;
}>();

const emit = defineEmits<{
    'update:show': [value: boolean];
}>();

const { handleApiRequestNotifications } = useNotificationsStore();

const deleteMutation = useMutation({
    mutationFn: async () => {
        const organizationId = getCurrentOrganizationId();
        if (!organizationId) {
            throw new Error('No organization ID found');
        }
        
        return api.removeMember(undefined, {
            params: {
                member: props.member.id,
                organization: organizationId,
            },
            queries: {
                delete_related: 'true',
            },
        });
    },
    onSuccess: () => {
        close();
        useMembersStore().fetchMembers();
    }
});

const form = useForm({
    canSubmitWhenInvalid: true,
    defaultValues: {
        confirmDelete: false,
    },
    onSubmit: async () => {
        await handleApiRequestNotifications(
            () => deleteMutation.mutateAsync(),
            'Member deleted successfully',
            'Error deleting member'
        );
    },
});

const close = () => {
    emit('update:show', false);
    form.reset();
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-text-primary">
                Delete Member
            </h2>

            <div class="mt-4 text-sm text-text-secondary">
                <p class="mb-4">
                    Are you sure you want to delete {{ member.name }}? This action cannot be undone.
                </p>
                <p class="mb-4">
                    This will permanently delete:
                </p>

                    <ul class="list-disc ml-6 mt-2">
                        <li>All time entries created by this member</li>
                        <li>Their project assignments</li>
                        <li>Their organization membership</li>
                    </ul>
                <p class="font-medium pt-4">
                    Note: Deleting time entries will affect all reports and statistics.
                </p>
            </div>

            <form
class="mt-6" @submit="
      (e) => {
        e.preventDefault();
        e.stopPropagation();
        form.handleSubmit();
      }
    ">
                <div class="flex items-start">
                        <form.Field
                            name="confirmDelete"
                            :validators="{
                                onSubmit: ({value}) => {
                                    if (!value) {
                                        return 'You must confirm that you understand the consequences of this action';
                                    }
                                    return '';
                                }
                            }"
                        >
                        <template #default="{ field }">
                            <div class="flex flex-col">
                            <div class="flex items-center space-x-3 text-sm">
                                <Checkbox
                                    :id="field.name"
                                    :name="field.name"
                                    :checked="field.state.value"
                                    @update:checked="field.handleChange"
                                    @blur="field.handleBlur"
                                />
                                <InputLabel :for="field.name" class="font-medium text-text-primary">
                                I understand that this will permanently delete all data related to this member
                                </InputLabel>
                            </div>
                            <InputError class="pl-7 pt-2" :message="field.state.meta.errors[0]" />
                            </div>
                        </template>
                        </form.Field>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="close">Cancel</SecondaryButton>
                    <form.Subscribe>
                        <template #default="{ canSubmit, isSubmitting }">
                            <DangerButton
                                type="submit"
                                :disabled="!canSubmit"
                            >
                                {{ isSubmitting  ? 'Deleting...' : 'Delete Member' }}
                            </DangerButton>
                        </template>
                    </form.Subscribe>
                </div>
            </form>
        </div>
    </Modal>
</template> 