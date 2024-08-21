<script setup lang="ts">
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { type Component, computed, nextTick, ref, watch } from 'vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import MultiselectDropdownItem from '@/packages/ui/src/Input/MultiselectDropdownItem.vue';
import type { Tag } from '@/packages/api/src';
import type { Placement } from '@floating-ui/vue';

const props = withDefaults(
    defineProps<{
        tags: Tag[];
        createTag: (name: string) => Promise<Tag | undefined>;
        align: Placement;
    }>(),
    {
        align: 'bottom-start',
    }
);

const model = defineModel<string[]>({
    default: [],
});

const searchInput = ref<HTMLInputElement | null>(null);
const open = ref(false);
const dropdownViewport = ref<Component | null>(null);

const searchValue = ref('');

function isTagSelected(id: string) {
    return model.value.includes(id);
}

function addOrRemoveTagFromSelection(id: string) {
    if (model.value.includes(id)) {
        model.value = model.value.filter((tagId) => tagId !== id);
    } else {
        model.value = [...model.value, id];
    }
    emit('changed');
}

const sortedTags = ref(props.tags);

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            searchInput.value?.focus();
        });

        // sort tags alphabetically
        sortedTags.value = [...props.tags].sort((a, b) => {
            const aIsSelected = model.value.includes(a.id);
            const bIsSelected = model.value.includes(b.id);
            if (aIsSelected === bIsSelected) {
                return a.name.localeCompare(b.name);
            }
            return model.value.includes(a.id) ? -1 : 1;
        });
        nextTick(() => {
            if (filteredTags.value.length > 0) {
                highlightedItemId.value = filteredTags.value[0].id;
            }
        });
    }
});

const filteredTags = computed(() => {
    return sortedTags.value.filter((tag: Tag) => {
        return tag.name
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

async function createAndAddTag(name: string) {
    const newTag = await props.createTag(name);
    if (newTag) {
        addOrRemoveTagFromSelection(newTag.id);
    }
    searchValue.value = '';
    return newTag;
}

async function addTagIfNoneExists() {
    if (highlightedItemId.value) {
        addOrRemoveTagFromSelection(highlightedItemId.value);
    }
}

watch(filteredTags, () => {
    if (filteredTags.value.length > 0) {
        highlightedItemId.value = filteredTags.value[0].id;
    }
});

function updateSearchValue(event: Event) {
    const newInput = (event.target as HTMLInputElement).value;
    if (newInput === ' ') {
        searchValue.value = '';
        const highlightedTagId = highlightedItemId.value;
        if (highlightedTagId) {
            const highlightedTag = props.tags.find(
                (tag) => tag.id === highlightedTagId
            );
            if (highlightedTag) {
                addOrRemoveTagFromSelection(highlightedTag.id);
            }
        }
    } else {
        searchValue.value = newInput;
    }
}

const emit = defineEmits<{
    changed: [];
    submit: [];
}>();

function toggleTag(newValue: string) {
    if (model.value.includes(newValue)) {
        model.value = [...model.value].filter((id) => id !== newValue);
    } else {
        model.value = [...model.value, newValue];
    }
    emit('changed');
}

function moveHighlightUp() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredTags.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === 0) {
            highlightedItemId.value =
                filteredTags.value[filteredTags.value.length - 1].id;
        } else {
            highlightedItemId.value =
                filteredTags.value[currentHightlightedIndex - 1].id;
        }
    }
}

function moveHighlightDown() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredTags.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === filteredTags.value.length - 1) {
            highlightedItemId.value = filteredTags.value[0].id;
        } else {
            highlightedItemId.value =
                filteredTags.value[currentHightlightedIndex + 1].id;
        }
    }
}

const highlightedItemId = ref<string | null>(null);
const highlightedItem = computed(() => {
    return props.tags.find((tag) => tag.id === highlightedItemId.value);
});

const showCreateTagModal = ref(false);
</script>

<template>
    <TagCreateModal
        :createTag="createAndAddTag"
        v-model:show="showCreateTagModal"></TagCreateModal>
    <Dropdown
        @submit="emit('submit')"
        v-model="open"
        :align="align"
        :closeOnContentClick="false">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
        <template #content>
            <input
                :value="searchValue"
                @input="updateSearchValue"
                @keydown.enter="addTagIfNoneExists"
                data-testid="tag_dropdown_search"
                @keydown.up.prevent="moveHighlightUp"
                @keydown.down.prevent="moveHighlightDown"
                ref="searchInput"
                class="bg-card-background border-0 placeholder-muted text-sm text-white py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                placeholder="Search for a Tag..." />
            <div ref="dropdownViewport" class="w-60 max-h-48 overflow-y-scroll">
                <div
                    v-for="tag in filteredTags"
                    :key="tag.id"
                    role="option"
                    :value="tag.id"
                    :class="{
                        'bg-card-background-active':
                            tag.id === highlightedItemId,
                    }"
                    data-testid="tag_dropdown_entries"
                    :data-tag-id="tag.id">
                    <MultiselectDropdownItem
                        :selected="isTagSelected(tag.id)"
                        @click="toggleTag(tag.id)"
                        :name="tag.name"></MultiselectDropdownItem>
                </div>
            </div>
            <div class="hover:bg-card-background-active rounded-b-lg">
                <button
                    @click="
                        open = false;
                        showCreateTagModal = true;
                    "
                    class="text-white w-full flex space-x-3 items-center px-4 py-3 text-xs font-semibold border-t border-card-background-separator">
                    <PlusCircleIcon
                        class="w-5 flex-shrink-0 text-icon-default"></PlusCircleIcon>
                    <span>Create new Tag</span>
                </button>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
