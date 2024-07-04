<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { colors } from '@/utils/color';

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div>
        <Dropdown align="bottom">
            <template #trigger>
                <button
                    class="p-2 bg-input-background hover:bg-tertiary transition rounded-full border border-input-border">
                    <div
                        :style="{
                            backgroundColor: model,
                        }"
                        class="w-6 h-6 rounded-full cursor-pointer"></div>
                </button>
            </template>
            <template #content>
                <div class="text-white grid grid-cols-6 gap-3 px-3 py-3">
                    <div
                        v-for="color in colors"
                        :key="color"
                        @click="model = color"
                        :style="{
                            backgroundColor: color,
                            boxShadow: `var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) ${color}30`,
                        }"
                        class="w-4 h-4 rounded-full cursor-pointer"></div>
                </div>
            </template>
        </Dropdown>
    </div>
</template>

<style scoped></style>
