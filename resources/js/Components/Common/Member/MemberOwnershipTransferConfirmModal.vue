<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

defineProps<{
    memberName: string;
}>();

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex justify-center">
                <span> Confirm Ownership Transfer </span>
            </div>
        </template>
        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <p class="py-1 text-center">
                        You are about to transfer the ownership of this
                        organization to {{ memberName }}.
                    </p>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                @click="emit('submit')"
                :class="{ 'opacity-25': saving }"
                :disabled="saving">
                Confirm Transfer
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
