<script setup lang="ts">
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import Badge from '@/packages/ui/src/Badge.vue';
import { type Component, computed } from 'vue';

const model = defineModel<string | null>({ default: null });
const props = defineProps<{
    groupByOptions: { value: string; label: string; icon: Component }[];
}>();
const icon = computed(() => {
    return props.groupByOptions.find((option) => option.value === model.value)
        ?.icon;
});
const title = computed(() => {
    return props.groupByOptions.find((option) => option.value === model.value)
        ?.label;
});
</script>

<template>
    <SelectDropdown
        v-model="model"
        :get-key-from-item="(item) => item.value"
        :get-name-for-item="(item) => item.label"
        :items="groupByOptions">
        <template #trigger>
            <Badge
                size="large"
                tag="button"
                class="cursor-pointer hover:bg-card-background transition space-x-5 flex">
                <component :is="icon" class="h-4 text-text-secondary"></component>
                <span> {{ title }} </span>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
