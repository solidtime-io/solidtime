<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useTagsStore } from '@/utils/useTags';
import type { Tag, UpdateTagBody } from '@/packages/api/src';

const { updateTag } = useTagsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    tag: Tag;
}>();

const tagBody = ref<UpdateTagBody>({
    name: props.tag.name,
});

async function submit() {
    saving.value = true;
    try {
        await updateTag({ tagId: props.tag.id, tagBody: tagBody.value });
        show.value = false;
    } finally {
        saving.value = false;
    }
}

const tagNameInput = ref<HTMLInputElement | null>(null);

useFocus(tagNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Tag </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <TextInput
                        id="tagName"
                        ref="tagNameInput"
                        v-model="tagBody.name"
                        type="text"
                        placeholder="Tag Name"
                        class="mt-1 block w-full"
                        required
                        autocomplete="tagName"
                        @keydown.enter="submit()" />
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
                Update Tag
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
