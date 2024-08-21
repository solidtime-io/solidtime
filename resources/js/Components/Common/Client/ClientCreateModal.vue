<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import type { CreateClientBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useClientsStore } from '@/utils/useClients';

const { createClient } = useClientsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const client = ref<CreateClientBody>({
    name: '',
});

async function submit() {
    await createClient(client.value);
    show.value = false;
}

const clientNameInput = ref<HTMLInputElement | null>(null);
useFocus(clientNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Client </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <TextInput
                        id="clientName"
                        ref="clientNameInput"
                        v-model="client.name"
                        type="text"
                        placeholder="Client Name"
                        @keydown.enter="submit"
                        class="mt-1 block w-full"
                        required
                        autocomplete="clientName" />
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Create Client
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
