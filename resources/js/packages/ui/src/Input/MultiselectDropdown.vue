<script setup lang="ts" generic="T">
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, type Ref, ref, watch } from 'vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';

const NONE_ID = 'none';

const model = defineModel<string[]>({
    default: [],
});

const props = defineProps<{
    items: T[];
    searchPlaceholder: string;
    getKeyFromItem: (item: T) => string;
    getNameForItem: (item: T) => string;
    noItemLabel?: string;
}>();

const open = ref(false);
const searchValue = ref('');
const sortedItems = ref<T[]>([]) as Ref<T[]>;

watch(open, (isOpen) => {
    if (isOpen) {
        searchValue.value = '';
        sortedItems.value = [...props.items].sort((a, b) => {
            const aSelected = model.value.includes(props.getKeyFromItem(a)) ? 0 : 1;
            const bSelected = model.value.includes(props.getKeyFromItem(b)) ? 0 : 1;
            return aSelected - bSelected;
        });
    }
});

const filteredItems = computed(() => {
    const search = searchValue.value.toLowerCase().trim();
    if (!search) return sortedItems.value;
    return sortedItems.value.filter((item) =>
        props.getNameForItem(item).toLowerCase().includes(search)
    );
});

const showNoItem = computed(() => {
    if (!props.noItemLabel) return false;
    const search = searchValue.value.toLowerCase().trim();
    if (!search) return true;
    return props.noItemLabel.toLowerCase().includes(search);
});

function toggleItem(id: string) {
    if (model.value.includes(id)) {
        model.value = model.value.filter((itemId) => itemId !== id);
    } else {
        model.value = [...model.value, id];
    }
    emit('changed');
}

const emit = defineEmits(['update:modelValue', 'changed', 'submit']);
</script>

<template>
    <Dropdown v-model="open" align="start" :close-on-content-click="false" @submit="emit('submit')">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
        <template #content>
            <ComboboxRoot
                v-model:search-term="searchValue"
                v-model:open="open"
                class="p-2"
                :filter-function="(val: string[]) => val">
                <ComboboxAnchor>
                    <ComboboxInput
                        class="w-full h-8 rounded-md border border-input-border bg-input-background px-3 text-sm text-text-primary placeholder:text-text-tertiary focus:outline-none"
                        :placeholder="searchPlaceholder" />
                </ComboboxAnchor>
                <ComboboxContent
                    :dismiss-able="false"
                    position="inline"
                    class="mt-2 min-w-60 max-w-80 max-h-60 overflow-y-auto">
                    <ComboboxViewport>
                        <ComboboxItem
                            v-if="showNoItem"
                            :value="NONE_ID"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active cursor-default"
                            @select.prevent="toggleItem(NONE_ID)">
                            <Checkbox
                                :checked="model.includes(NONE_ID)"
                                aria-hidden="true"
                                :tabindex="-1"
                                class="pointer-events-none" />
                            <span class="truncate">{{ noItemLabel }}</span>
                        </ComboboxItem>
                        <ComboboxItem
                            v-for="item in filteredItems"
                            :key="getKeyFromItem(item)"
                            :value="getKeyFromItem(item)"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active cursor-default"
                            @select.prevent="toggleItem(getKeyFromItem(item))">
                            <Checkbox
                                :checked="model.includes(getKeyFromItem(item))"
                                aria-hidden="true"
                                :tabindex="-1"
                                class="pointer-events-none" />
                            <span class="truncate">{{ getNameForItem(item) }}</span>
                        </ComboboxItem>
                    </ComboboxViewport>
                </ComboboxContent>
            </ComboboxRoot>
        </template>
    </Dropdown>
</template>
