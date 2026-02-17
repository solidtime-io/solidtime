<script setup lang="ts">
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, ref, watch } from 'vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import type { Tag } from '@/packages/api/src';
import { Button } from '@/packages/ui/src/Buttons';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';

const NONE_ID = 'none';
const NO_TAG_LABEL = 'No Tag';

const props = withDefaults(
    defineProps<{
        tags: Tag[];
        createTag: (name: string) => Promise<Tag | undefined>;
        align?: 'center' | 'end' | 'start';
        showNoTagOption?: boolean;
    }>(),
    {
        align: 'start',
        showNoTagOption: true,
    }
);

const model = defineModel<string[]>({
    default: [],
});

const open = ref(false);
const searchValue = ref('');
const sortedTags = ref<Tag[]>([]);

watch(open, (isOpen) => {
    if (isOpen) {
        searchValue.value = '';
        sortedTags.value = [...props.tags].sort((a, b) => {
            const aSelected = model.value.includes(a.id) ? 0 : 1;
            const bSelected = model.value.includes(b.id) ? 0 : 1;
            return aSelected - bSelected;
        });
    }
});

const filteredTags = computed(() => {
    const search = searchValue.value.toLowerCase().trim();
    if (!search) return sortedTags.value;
    return sortedTags.value.filter((tag) => tag.name.toLowerCase().includes(search));
});

const showNoTag = computed(() => {
    if (!props.showNoTagOption) return false;
    const search = searchValue.value.toLowerCase().trim();
    if (!search) return true;
    return NO_TAG_LABEL.toLowerCase().includes(search);
});

function toggleTag(id: string) {
    if (model.value.includes(id)) {
        model.value = model.value.filter((tagId) => tagId !== id);
    } else {
        model.value = [...model.value, id];
    }
    emit('changed');
}

async function createAndAddTag(name: string) {
    const newTag = await props.createTag(name);
    if (newTag) {
        toggleTag(newTag.id);
    }
    searchValue.value = '';
    return newTag;
}

const emit = defineEmits<{
    changed: [];
    submit: [];
}>();

const showCreateTagModal = ref(false);
</script>

<template>
    <TagCreateModal
        v-if="showCreateTagModal"
        v-model:show="showCreateTagModal"
        :create-tag="createAndAddTag"></TagCreateModal>
    <Dropdown
        v-model="open"
        :align="align"
        :close-on-content-click="false"
        @submit="emit('submit')">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
        <template #content>
            <ComboboxRoot
                v-model:search-term="searchValue"
                :open="true"
                class="p-2"
                :filter-function="(val: string[]) => val">
                <ComboboxAnchor>
                    <ComboboxInput
                        data-testid="tag_dropdown_search"
                        class="w-full rounded-md border border-input-border bg-input-background px-3 py-1.5 text-sm text-text-primary placeholder:text-text-tertiary focus:outline-none"
                        placeholder="Search for a Tag..." />
                </ComboboxAnchor>
                <ComboboxContent
                    :dismiss-able="false"
                    position="inline"
                    class="mt-2 w-60 max-h-60 overflow-y-auto">
                    <ComboboxViewport>
                        <ComboboxItem
                            v-if="showNoTag"
                            :value="NONE_ID"
                            data-testid="tag_dropdown_entries"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active cursor-default"
                            @select.prevent="toggleTag(NONE_ID)">
                            <Checkbox
                                :checked="model.includes(NONE_ID)"
                                aria-hidden="true"
                                :tabindex="-1"
                                class="pointer-events-none" />
                            <span class="truncate">{{ NO_TAG_LABEL }}</span>
                        </ComboboxItem>
                        <ComboboxItem
                            v-for="tag in filteredTags"
                            :key="tag.id"
                            :value="tag.id"
                            data-testid="tag_dropdown_entries"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active cursor-default"
                            @select.prevent="toggleTag(tag.id)">
                            <Checkbox
                                :checked="model.includes(tag.id)"
                                aria-hidden="true"
                                :tabindex="-1"
                                class="pointer-events-none" />
                            <span class="truncate">{{ tag.name }}</span>
                        </ComboboxItem>
                    </ComboboxViewport>
                </ComboboxContent>
                <div class="mt-1 border-t border-card-background-separator pt-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        class="w-full justify-start gap-2 px-2 py-1.5 text-sm text-text-primary"
                        @click="
                            open = false;
                            showCreateTagModal = true;
                        ">
                        <PlusCircleIcon class="w-4 h-4 flex-shrink-0 text-icon-default" />
                        <span>Create new Tag</span>
                    </Button>
                </div>
            </ComboboxRoot>
        </template>
    </Dropdown>
</template>
