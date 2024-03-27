<script setup lang="ts">
import TagDropdown from '@/Components/Common/Tag/TagDropdown.vue';
import { twMerge } from 'tailwind-merge';
import { TagIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';

const emit = defineEmits(['changed']);
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
</script>

<template>
    <TagDropdown @changed="emit('changed')" v-model="model">
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
