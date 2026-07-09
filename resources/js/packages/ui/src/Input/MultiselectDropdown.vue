<script setup lang="ts" generic="T">
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, ref, watch } from 'vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
    ComboboxVirtualizer,
} from 'reka-ui';

const NONE_ID = 'none';

// height of one row (px-2 py-1.5 text-sm → 12px padding + 20px line box).
// Rows are uniform single-line, so a fixed size is exact enough for the virtualizer and avoids
// any per-row DOM measurement.
const ROW_HEIGHT = 32;

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
// Pinned on open so rows don't re-sort while toggling; the item list itself stays reactive.
const pinnedSelection = ref<Set<string>>(new Set());

watch(open, (isOpen) => {
    if (isOpen) {
        searchValue.value = '';
        pinnedSelection.value = new Set(model.value);
    }
});

const sortedItems = computed(() => {
    return [...props.items].sort((a, b) => {
        const aSelected = pinnedSelection.value.has(props.getKeyFromItem(a)) ? 0 : 1;
        const bSelected = pinnedSelection.value.has(props.getKeyFromItem(b)) ? 0 : 1;
        if (aSelected !== bSelected) return aSelected - bSelected;
        return props.getNameForItem(a).localeCompare(props.getNameForItem(b));
    });
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

// A single flat list for the virtualizer. The optional "no item" entry is folded in as the
// first row so the whole list (including it) is virtualized through one ComboboxVirtualizer.
type Row = { kind: 'none' } | { kind: 'item'; item: T };

const rows = computed<Row[]>(() => {
    const itemRows = filteredItems.value.map((item): Row => ({ kind: 'item', item }));
    return showNoItem.value ? [{ kind: 'none' }, ...itemRows] : itemRows;
});

function keyForRow(row: Row): string {
    return row.kind === 'none' ? NONE_ID : props.getKeyFromItem(row.item);
}

function nameForRow(row: Row): string {
    return row.kind === 'none' ? (props.noItemLabel ?? '') : props.getNameForItem(row.item);
}

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
            <!-- kept open so the list stays visible during the popover close animation -->
            <ComboboxRoot
                :open="true"
                class="p-2"
                :ignore-filter="true"
                @update:open="
                    (value: boolean) => {
                        if (!value) open = false;
                    }
                ">
                <ComboboxAnchor>
                    <ComboboxInput
                        v-model="searchValue"
                        class="w-full h-8 rounded-md border border-input-border bg-input-background px-3 text-sm text-text-primary placeholder:text-text-tertiary focus:outline-none"
                        :placeholder="searchPlaceholder" />
                </ComboboxAnchor>
                <ComboboxContent
                    :dismiss-able="false"
                    position="inline"
                    class="mt-2 min-w-60 max-w-80">
                    <ComboboxViewport class="max-h-60 overflow-y-auto">
                        <ComboboxVirtualizer
                            v-slot="{ option }"
                            :options="rows"
                            :estimate-size="ROW_HEIGHT"
                            :text-content="nameForRow">
                            <ComboboxItem
                                :value="keyForRow(option)"
                                class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active cursor-default"
                                @select.prevent="toggleItem(keyForRow(option))">
                                <Checkbox
                                    :checked="model.includes(keyForRow(option))"
                                    aria-hidden="true"
                                    :tabindex="-1"
                                    class="pointer-events-none" />
                                <span class="truncate">{{ nameForRow(option) }}</span>
                            </ComboboxItem>
                        </ComboboxVirtualizer>
                    </ComboboxViewport>
                </ComboboxContent>
            </ComboboxRoot>
        </template>
    </Dropdown>
</template>
