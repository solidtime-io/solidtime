<script setup lang="ts">
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/Components/Dropdown.vue';
import { type Component, computed, nextTick, ref, watch } from 'vue';
import TagDropdownItem from '@/Components/Common/Tag/TagDropdownItem.vue';
import { useTagsStore } from '@/utils/useTags';
import { storeToRefs } from 'pinia';

const tagsStore = useTagsStore();
const { tags } = storeToRefs(tagsStore);

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
        model.value.push(id);
    }
    emit('changed');
}

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            searchInput.value?.focus();
        });

        // sort tags alphabetically
        tags.value.sort((a, b) => {
            const aIsSelected = model.value.includes(a.id);
            const bIsSelected = model.value.includes(b.id);
            if (aIsSelected === bIsSelected) {
                return a.name.localeCompare(b.name);
            }
            return model.value.includes(a.id) ? -1 : 1;
        });
    }
});

const filteredTags = computed(() => {
    return tags.value.filter((tag) => {
        return tag.name
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

async function addTagIfNoneExists() {
    if (searchValue.value.length > 0 && filteredTags.value.length === 0) {
        const newTag = await tagsStore.createTag(searchValue.value);
        if (newTag) {
            addOrRemoveTagFromSelection(newTag.id);
        }
        searchValue.value = '';
    } else {
        if (highlightedItemId.value) {
            addOrRemoveTagFromSelection(highlightedItemId.value);
        }
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
            const highlightedTag = tags.value.find(
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

const emit = defineEmits(['update:modelValue', 'changed']);

function toggleTag(newValue: string) {
    if (model.value.includes(newValue)) {
        model.value = model.value.filter((id) => id !== newValue);
    } else {
        model.value.push(newValue);
    }
    nextTick(() => {
        emit('changed');
    });
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
    console.log('move down');
}

const highlightedItemId = ref<string | null>(null);
const highlightedItem = computed(() => {
    return tags.value.find((tag) => tag.id === highlightedItemId.value);
});
</script>

<template>
    <Dropdown width="120" v-model="open" :closeOnContentClick="false">
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
                placeholder="Search for a tag..." />
            <div ref="dropdownViewport" class="w-60">
                <div
                    v-if="searchValue.length > 0 && filteredTags.length === 0"
                    class="bg-card-background-active">
                    <div
                        @click="addTagIfNoneExists"
                        class="text-white flex space-x-3 items-center px-4 py-3 text-xs font-medium border-t rounded-b-lg border-card-background-separator">
                        <PlusCircleIcon
                            class="w-5 flex-shrink-0"></PlusCircleIcon>
                        <span>Add "{{ searchValue }}" as a new Tag</span>
                    </div>
                </div>
                <div v-else></div>
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
                    <TagDropdownItem
                        :selected="isTagSelected(tag.id)"
                        @click="toggleTag(tag.id)"
                        :name="tag.name"></TagDropdownItem>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
