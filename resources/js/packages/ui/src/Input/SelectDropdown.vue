<script setup lang="ts" generic="T">
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, nextTick, ref, watch } from 'vue';
import SelectDropdownItem from '@/packages/ui/src/Input/SelectDropdownItem.vue';
import { onKeyStroke } from '@vueuse/core';
import { type Placement } from '@floating-ui/vue';
import { twMerge } from 'tailwind-merge';
const model = defineModel<string | null>({
    default: null,
});

const open = defineModel('open', {
    default: false,
});

const props = withDefaults(
    defineProps<{
        items: T[];
        getKeyFromItem: (item: T) => string | null;
        getNameForItem: (item: T) => string;
        align?: Placement;
        class?: string;
    }>(),
    {
        align: 'bottom-start',
    }
);

const dropdownViewport = ref<HTMLDivElement | null>(null);

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

const highlightedItemId = ref<string | null>(model.value);

watch(model, () => {
    highlightedItemId.value = model.value;
});

watch(filteredItems, () => {
    if (
        filteredItems.value.length > 0 &&
        filteredItems.value.find(
            (item) => props.getKeyFromItem(item) === highlightedItemId.value
        ) === undefined
    ) {
        highlightedItemId.value = props.getKeyFromItem(filteredItems.value[0]);
    }
});

watch(highlightedItemId, () => {
    if (highlightedItemId.value) {
        const highlightedDomElement = dropdownViewport.value?.querySelector(
            `[data-select-id="${highlightedItemId.value}"]`
        ) as HTMLElement;

        highlightedDomElement?.scrollIntoView({
            block: 'nearest',
            inline: 'nearest',
        });
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
        nextTick(() => {
            const highlightedDomElement = dropdownViewport.value?.querySelector(
                `[data-select-id="${model.value}"]`
            ) as HTMLElement;
            dropdownViewport.value?.scrollTo({
                top: highlightedDomElement?.offsetTop ?? 0,
                behavior: 'instant',
            });
        });
    }
});
</script>

<template>
    <Dropdown v-model="open" :align="align" :close-on-content-click="false">
        <template #trigger>
            <slot name="trigger"> </slot>
        </template>
        <template #content>
            <div
                ref="dropdownViewport"
                :class="
                    twMerge(
                        'w-60 py-1.5 max-h-60 overflow-y-scroll',
                        props.class
                    )
                ">
                <div
                    v-for="item in filteredItems"
                    :key="props.getKeyFromItem(item) ?? 'none'"
                    role="option"
                    :data-select-id="props.getKeyFromItem(item)"
                    :value="props.getKeyFromItem(item)"
                    :data-item-id="props.getKeyFromItem(item)">
                    <SelectDropdownItem
                        :highlighted="
                            props.getKeyFromItem(item) === highlightedItemId
                        "
                        :selected="props.getKeyFromItem(item) === model"
                        :name="props.getNameForItem(item)"
                        @mouseenter="
                            highlightedItemId = props.getKeyFromItem(item)
                        "
                        @click="setItem(props.getKeyFromItem(item))"></SelectDropdownItem>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
