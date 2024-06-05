<script setup lang="ts">
import SelectDropdown from '@/Components/Common/SelectDropdown.vue';
import Badge from '@/Components/Common/Badge.vue';
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
        <template v-slot:trigger>
            <Badge
                size="large"
                class="cursor-pointer hover:bg-card-background transition space-x-5 flex">
                <component :is="icon" class="h-4 text-muted"></component>
                <span> {{ title }} </span>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
