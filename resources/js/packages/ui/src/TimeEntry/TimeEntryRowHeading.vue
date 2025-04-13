<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import {
    formatDate,
    formatHumanReadableDuration,
    formatWeekday,
} from '@/packages/ui/src/utils/time';
import Checkbox from '../Input/Checkbox.vue';
defineProps<{
    date: string;
    duration: number;
    checked: boolean;
}>();
const emit = defineEmits<{
    selectAll: [];
    unselectAll: [];
}>();

function selectUnselectAll(value: boolean) {
    if (value) {
        emit('selectAll');
    } else {
        emit('unselectAll');
    }
}
</script>

<template>
    <div
        class="bg-row-heading-background border-t border-b border-row-heading-border py-1 text-xs sm:text-sm">
        <MainContainer>
            <div class="flex group justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="w-5">
                        <svg
                            class="w-4 sm:w-5 text-icon-default group-hover:hidden block"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <g fill="none">
                                <path
                                    d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.02-.022m-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z" />
                                <path
                                    fill="currentColor"
                                    d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7zm-5-9a1 1 0 0 1 1 1v1h2a2 2 0 0 1 2 2v3H3V7a2 2 0 0 1 2-2h2V4a1 1 0 0 1 2 0v1h6V4a1 1 0 0 1 1-1" />
                            </g>
                        </svg>
                        <Checkbox
                            :checked="checked"
                            class="group-hover:block hidden"
                            @update:checked="selectUnselectAll"></Checkbox>
                    </div>
                    <span class="font-semibold text-text-primary">
                        {{ formatWeekday(date) }}
                    </span>
                    <span class="font-semibold text-text-secondary">
                        {{ formatDate(date) }}
                    </span>
                </div>
                <div class="text-text-secondary pr-[90px] lg:pr-[92px]">
                    <span class="font-semibold">
                        {{ formatHumanReadableDuration(duration) }}
                    </span>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
