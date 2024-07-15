<script setup lang="ts">
import TagDropdown from '@/Components/Common/Tag/TagDropdown.vue';
import { twMerge } from 'tailwind-merge';
import { TagIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';
import type { Tag } from '@/utils/api';

const emit = defineEmits<{
    changed: [];
    createTag: [name: string, callback: (tag: Tag) => void];
}>();

const model = defineModel({
    default: [],
});
const iconColorClasses = computed(() => {
    if (model.value.length > 0) {
        return 'text-accent-200/80 focus:text-accent-200 hover:text-accent-200';
    } else {
        return 'text-icon-default hover:text-icon-active focus:text-icon-active';
    }
});
defineProps<{
    tags: Tag[];
}>();
</script>

<template>
    <TagDropdown
        @createTag="(...args) => $emit('createTag', ...args)"
        @changed="emit('changed')"
        v-model="model"
        :tags="tags">
        <template #trigger>
            <button
                data-testid="tag_dropdown"
                :class="
                    twMerge(
                        iconColorClasses,
                        'flex-shrink-0 ring-0 focus:outline-none focus:ring-0 transition focus-visible:bg-card-background-separator hover:bg-card-background-separator rounded-full w-7 sm:w-10 h-7 sm:h-10 flex items-center justify-center'
                    )
                ">
                <TagIcon class="w-5 sm:w-6 h-5 sm:h-6"></TagIcon>
                <span
                    v-if="model.length > 1"
                    class="font-extrabold absolute rounded-full text-xs w-3 h-3 block top-[15px] rotate-[45deg] right-[14px] text-card-background">
                    {{ model.length }}
                </span>
            </button>
        </template>
    </TagDropdown>
</template>

<style scoped></style>
