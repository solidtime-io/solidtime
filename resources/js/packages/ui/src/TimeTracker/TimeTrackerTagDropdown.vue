<script setup lang="ts">
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import { twMerge } from 'tailwind-merge';
import { TagIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';
import type { Tag } from '@/packages/api/src';

const emit = defineEmits<{
    changed: [];
}>();

const model = defineModel<string[]>({
    default: [],
});
const iconColorClasses = computed(() => {
    if (model.value.length > 0) {
        return 'text-input-select-active focus:text-input-select-active-hover hover:text-input-select-active-hover';
    } else {
        return 'text-icon-default hover:text-icon-active focus:text-icon-active';
    }
});
defineProps<{
    tags: Tag[];
    createTag: (name: string) => Promise<Tag | undefined>;
}>();
</script>

<template>
    <TagDropdown
        v-model="model"
        :create-tag
        :tags="tags"
        @changed="emit('changed')">
        <template #trigger>
            <button
                data-testid="tag_dropdown"
                :class="
                    twMerge(
                        iconColorClasses,
                        'flex-shrink-0 ring-0 focus:outline-none focus:ring-2 focus:ring-ring transition focus-visible:bg-card-background-separator hover:bg-card-background-separator rounded-full w-10 h-10 flex items-center justify-center'
                    )
                ">
                <TagIcon class="w-5 h-5 lg:h-6 lg:w-6"></TagIcon>
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
