<script setup lang="ts" generic="T">
import Dropdown from '@/Components/Dropdown.vue';
import { type Component, computed, ref, watch } from 'vue';
import SelectDropdownItem from '@/Components/Common/SelectDropdownItem.vue';
import { onKeyStroke } from '@vueuse/core';
import { type Placement } from '@floating-ui/vue';

const model = defineModel<string | null>({
    default: null,
});

const props = withDefaults(
    defineProps<{
        items: T[];
        getKeyFromItem: (item: T) => string | null;
        getNameForItem: (item: T) => string;
        align?: Placement;
    }>(),
    {
        align: 'bottom-start',
    }
);

const open = ref(false);
const dropdownViewport = ref<Component | null>(null);

const searchValue = ref('');

// DropdownMultiselect
const filteredItems = computed<T[]>(() => {
    return props.items.filter((item: T) => {
        return props
            .getNameForItem(item)
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

watch(filteredItems, () => {
    if (filteredItems.value.length > 0) {
        highlightedItemId.value = props.getKeyFromItem(filteredItems.value[0]);
    }
});

const emit = defineEmits(['update:modelValue', 'changed']);

function setItem(newValue: string | null) {
    model.value = newValue;
    emit('changed');
    open.value = false;
}

function moveHighlightUp() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredItems.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === 0) {
            highlightedItemId.value = props.getKeyFromItem(
                filteredItems.value[filteredItems.value.length - 1]
            );
        } else {
            highlightedItemId.value = props.getKeyFromItem(
                filteredItems.value[currentHightlightedIndex - 1]
            );
        }
    }
}

function moveHighlightDown() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredItems.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === filteredItems.value.length - 1) {
            highlightedItemId.value = props.getKeyFromItem(
                filteredItems.value[0]
            );
        } else {
            highlightedItemId.value = props.getKeyFromItem(
                filteredItems.value[currentHightlightedIndex + 1]
            );
        }
    }
}

const highlightedItemId = ref<string | null>(null);
const highlightedItem = computed(() => {
    return props.items.find(
        (item) => props.getKeyFromItem(item) === highlightedItemId.value
    );
});

onKeyStroke('ArrowDown', (e) => {
    if (open.value === true) {
        moveHighlightDown();
        e.preventDefault();
    }
});

onKeyStroke('ArrowUp', (e) => {
    if (open.value === true) {
        moveHighlightUp();
        e.preventDefault();
    }
});

onKeyStroke('Enter', (e) => {
    if (open.value === true) {
        setItem(highlightedItemId.value);
        e.preventDefault();
    }
});

watch(open, () => {
    if (open.value === true) {
        highlightedItemId.value = model.value;
    }
});
</script>

<template>
    <Dropdown v-model="open" :align="align" :closeOnContentClick="false">
        <template #trigger>
            <slot name="trigger"> </slot>
        </template>
        <template #content>
            <div ref="dropdownViewport" class="w-60">
                <div
                    v-for="item in filteredItems"
                    :key="props.getKeyFromItem(item) ?? 'none'"
                    role="option"
                    :value="props.getKeyFromItem(item)"
                    :class="{
                        'bg-card-background-active':
                            props.getKeyFromItem(item) === highlightedItemId,
                    }"
                    :data-item-id="props.getKeyFromItem(item)">
                    <SelectDropdownItem
                        :selected="props.getKeyFromItem(item) === model"
                        @click="setItem(props.getKeyFromItem(item))"
                        :name="props.getNameForItem(item)"></SelectDropdownItem>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
