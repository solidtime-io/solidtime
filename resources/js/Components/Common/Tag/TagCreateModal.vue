<script setup lang="ts">
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import type { CreateTagBody } from '@/utils/api';
import { useTagsStore } from '@/utils/useTags';
const show = defineModel('show', { default: false });
const saving = ref(false);

const { createTag } = useTagsStore();

const tag = ref<CreateTagBody>({
    name: '',
});

async function submit() {
    await createTag(tag.value.name);
    show.value = false;
}

const tagNameInput = ref<HTMLInputElement | null>(null);
useFocus(tagNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Tags </span>
            </div>
        </template>
        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <TextInput
                        id="tagName"
                        ref="tagNameInput"
                        v-model="tag.name"
                        type="text"
                        placeholder="Tag Name"
                        class="mt-1 block w-full"
                        required
                        autocomplete="tagName" />
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
                Create Tag
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
