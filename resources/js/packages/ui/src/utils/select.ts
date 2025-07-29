import { computed, type Ref, watch } from 'vue';
import { onKeyStroke } from '@vueuse/core';

export function useSelectEvents<Type>(
    filteredItems: Ref<Array<Type>>,
    highlightedItemId: Ref<string | null>,
    getKeyFromItem: (item: Type) => string,
    open: Ref<boolean>
) {
    function moveHighlightUp() {
        if (highlightedItem.value) {
            const currentHightlightedIndex = filteredItems.value.indexOf(highlightedItem.value);
            if (currentHightlightedIndex === 0) {
                highlightedItemId.value = getKeyFromItem(
                    filteredItems.value[filteredItems.value.length - 1]
                );
            } else {
                highlightedItemId.value = getKeyFromItem(
                    filteredItems.value[currentHightlightedIndex - 1]
                );
            }
        } else {
            highlightedItemId.value = getKeyFromItem(
                filteredItems.value[filteredItems.value.length - 1]
            );
        }
    }

    function moveHighlightDown() {
        if (highlightedItem.value) {
            const currentHightlightedIndex = filteredItems.value.indexOf(highlightedItem.value);
            if (currentHightlightedIndex === filteredItems.value.length - 1) {
                highlightedItemId.value = getKeyFromItem(filteredItems.value[0]);
            } else {
                highlightedItemId.value = getKeyFromItem(
                    filteredItems.value[currentHightlightedIndex + 1]
                );
            }
        } else {
            highlightedItemId.value = getKeyFromItem(filteredItems.value[0]);
        }
    }

    const highlightedItem = computed(() => {
        return filteredItems.value.find((item) => getKeyFromItem(item) === highlightedItemId.value);
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

    watch(open, (newOpen) => {
        if (newOpen === false) {
            highlightedItemId.value = null;
        }
    });
}
