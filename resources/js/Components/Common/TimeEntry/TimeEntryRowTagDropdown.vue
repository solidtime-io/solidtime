<script setup lang="ts">
import TagDropdown from '@/Components/Common/Tag/TagDropdown.vue';
import { computed } from 'vue';
import TagBadge from '@/Components/Common/Tag/TagBadge.vue';
import type { Tag } from '@/utils/api';
import { useTagsStore } from '@/utils/useTags';
import { storeToRefs } from 'pinia';

const tagsStore = useTagsStore();
const { tags } = storeToRefs(tagsStore);
const emit = defineEmits(['changed']);
const model = defineModel<string[]>({
    default: [],
});

const timeEntryTags = computed<Tag[]>(() => {
    return tags.value.filter((tag) => model.value.includes(tag.id));
});
</script>

<template>
    <TagDropdown @changed="emit('changed', model)" v-model="model">
        <template #trigger>
            <button data-testid="time_entry_tag_dropdown">
                <TagBadge
                    :border="false"
                    size="large"
                    class="border-0"
                    :name="
                        timeEntryTags.map((tag) => tag.name).join(', ')
                    "></TagBadge>
            </button>
        </template>
    </TagDropdown>
</template>

<style scoped></style>
