<script setup lang="ts">
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import { computed } from 'vue';
import TagBadge from '@/packages/ui/src/Tag/TagBadge.vue';
import type { Tag } from '@/packages/api/src';

const props = defineProps<{
    tags: Tag[];
    createTag: (name: string) => Promise<Tag | undefined>;
}>();

const emit = defineEmits<{
    changed: [model: string[]];
}>();

const model = defineModel<string[]>({
    default: [],
});

const timeEntryTags = computed<Tag[]>(() => {
    return props.tags.filter((tag) => model.value.includes(tag.id));
});
</script>
<template>
    <TagDropdown
        :tags="tags"
        align="bottom-end"
        :createTag
        @changed="emit('changed', model)"
        v-model="model">
        <template #trigger>
            <button
                data-testid="time_entry_tag_dropdown"
                class="opacity-50 group-hover:opacity-100 group/dropdown focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:opacity-100 transition focus:bg-card-background-separator hover:bg-card-background-separator rounded-full flex items-center justify-center">
                <TagBadge
                    :border="false"
                    size="large"
                    class="border-0 sm:px-1.5 text-icon-default group-focus-within/dropdown:text-text-primary"
                    :name="
                        timeEntryTags.map((tag: Tag) => tag.name).join(', ')
                    "></TagBadge>
            </button>
        </template>
    </TagDropdown>
</template>

<style scoped></style>
