<script setup lang="ts">
import { PlusCircleIcon, TagIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/Components/Dropdown.vue';
import {
    type Component,
    computed,
    nextTick,
    onMounted,
    ref,
    watch,
    watchEffect,
} from 'vue';
import TagDropdownItem from '@/Components/common/TagDropdownItem.vue';
import { twMerge } from 'tailwind-merge';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';
import { useTagsStore } from '@/utils/useTags';
import { storeToRefs } from 'pinia';

const tagsStore = useTagsStore();
const { tags } = storeToRefs(tagsStore);

const emit = defineEmits(['changed']);

const model = defineModel<string[]>({
    default: [],
});

onMounted(async () => {
    await tagsStore.fetchTags();
});

const searchInput = ref<Component | null>(null);
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

const iconColorClasses = computed(() => {
    if (model.value.length > 0) {
        return 'text-accent-200/80 focus:text-accent-200 hover:text-accent-200';
    } else {
        return 'text-icon-default hover:text-icon-active focus:text-icon-active';
    }
});

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            // @ts-expect-error We need to access the actual HTML Element to focus as radix-vue does not support any other way right now
            searchInput.value?.$el?.focus();
        });

        tags.value.sort((a) => {
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

const showAllTags = ref(false);

const shownTags = computed(() => {
    if (showAllTags.value) {
        return filteredTags.value;
    } else {
        return filteredTags.value.slice(0, 5);
    }
});

const moreTagsAvailable = computed(() => {
    return filteredTags.value.length - shownTags.value.length;
});

async function addTagIfNoneExists() {
    if (searchValue.value.length > 0 && filteredTags.value.length === 0) {
        const newTag = await tagsStore.createTag(searchValue.value);
        addOrRemoveTagFromSelection(newTag.id);
        searchValue.value = '';
    }
}

function removeTagLimit() {
    showAllTags.value = true;
}

watchEffect(() => {
    if (searchValue.value === ' ') {
        nextTick(() => {
            searchValue.value = '';
            const currentSelectedItem =
                // @ts-expect-error We need to access the actual HTML Element to focus as radix-vue does not support any other way right now
                dropdownViewport.value?.$el?.querySelector(
                    '[data-highlighted]'
                );
            const highlightedTagId = currentSelectedItem?.getAttribute(
                'data-tag-id'
            ) as string;
            if (highlightedTagId) {
                const highlightedTag = tags.value.find(
                    (tag) => tag.id === highlightedTagId
                );
                if (highlightedTag) {
                    addOrRemoveTagFromSelection(highlightedTag.id);
                }
            }
        });
    }
});

function updateValue(e: string[]) {
    model.value = e;
    emit('changed');
}
</script>

<template>
    <Dropdown width="120" v-model="open" :closeOnContentClick="false">
        <template #trigger>
            <button
                data-testid="tag_dropdown"
                :class="
                    twMerge(
                        iconColorClasses,
                        'flex-shrink-0 ring-0 focus:outline-none focus:ring-0 transition focus:bg-card-background-seperator hover:bg-card-background-seperator rounded-full w-11 h-11 flex items-center justify-center'
                    )
                ">
                <TagIcon class="w-7 h-7"></TagIcon>
                <div
                    v-if="model.length > 1"
                    class="font-extrabold absolute rounded-full text-xs w-3 h-3 block top-[15px] rotate-[45deg] right-[14px] text-card-background">
                    {{ model.length }}
                </div>
            </button>
        </template>
        <template #content>
            <ComboboxRoot
                multiple
                :open="open"
                @update:modelValue="updateValue"
                v-model:searchTerm="searchValue"
                class="relative">
                <ComboboxAnchor>
                    <ComboboxInput
                        @keydown.enter="addTagIfNoneExists"
                        data-testid="tag_dropdown_search"
                        ref="searchInput"
                        class="bg-card-background border-0 placeholder-muted text-white py-2.5 focus:ring-0 border-b border-card-background-seperator focus:border-card-background-seperator w-full"
                        placeholder="Search for a tag..." />
                </ComboboxAnchor>
                <ComboboxContent>
                    <ComboboxViewport ref="dropdownViewport" class="w-60">
                        <ComboboxEmpty>
                            <div
                                v-if="searchValue.length > 0"
                                class="bg-card-background-active">
                                <div
                                    class="flex space-x-3 items-center px-4 py-3 text-sm font-medium border-t rounded-b-lg border-card-background-seperator">
                                    <PlusCircleIcon
                                        class="w-5 flex-shrink-0"></PlusCircleIcon>
                                    <span
                                        >Add "{{ searchValue }}" as a new
                                        Tag</span
                                    >
                                </div>
                            </div>
                            <div v-else></div>
                        </ComboboxEmpty>
                        <ComboboxItem
                            v-for="tag in shownTags"
                            :key="tag.id"
                            :value="tag.id"
                            class="data-[highlighted]:bg-card-background-active"
                            data-testid="tag_dropdown_entries"
                            :data-tag-id="tag.id">
                            <TagDropdownItem
                                :selected="isTagSelected(tag.id)"
                                :name="tag.name"></TagDropdownItem>
                        </ComboboxItem>
                    </ComboboxViewport>
                    <button
                        @click="removeTagLimit"
                        v-if="moreTagsAvailable > 0"
                        class="border-t hover:text-white hover:bg-card-background-active px-2 text-center font-semibold py-2 border-t-card-background-seperator">
                        Show all
                    </button>
                </ComboboxContent>
            </ComboboxRoot>
        </template>
    </Dropdown>
</template>

<style scoped></style>
