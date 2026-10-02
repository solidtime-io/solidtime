<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Check, ChevronDown } from '@lucide/vue';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxInput,
    ComboboxItem,
    ComboboxList,
    ComboboxTrigger,
    ComboboxViewport,
    ComboboxVirtualizer,
} from '@/packages/ui/src/combobox';
import { Button } from '@/packages/ui/src/Buttons';
import { useTimezonesQuery } from '@/utils/useTimezonesQuery';

// height of one row (py-1.5 text-sm → 12px padding + 20px line box)
const ROW_HEIGHT = 32;

const model = defineModel<string>({ required: true });

defineProps<{
    id?: string;
    disabled?: boolean;
}>();

const { timezones } = useTimezonesQuery();

const open = ref(false);
const searchValue = ref('');

watch(open, (isOpen) => {
    if (isOpen) searchValue.value = '';
});

const filteredTimezones = computed(() => {
    // Match "new york" against "America/New_York"
    const search = searchValue.value.toLowerCase().trim().replace(/\s+/g, '_');
    if (!search) return timezones.value;
    return timezones.value.filter((timezone) => timezone.toLowerCase().includes(search));
});

// Reka fills the input with the selected value on open, which would filter the list down to it
function emptySearch() {
    return '';
}

function timezoneName(timezone: string) {
    return timezone;
}
</script>

<template>
    <Combobox v-model="model" v-model:open="open" :ignore-filter="true" :disabled="disabled">
        <ComboboxAnchor>
            <ComboboxTrigger as-child>
                <!-- aria-label replaces Reka's hardcoded "Show popup" name -->
                <Button
                    :id="id"
                    type="button"
                    variant="input"
                    :aria-label="model ? `Timezone: ${model}` : 'Timezone'"
                    class="w-full justify-between font-normal">
                    <span v-if="model" class="truncate">{{ model }}</span>
                    <span v-else class="truncate text-muted-foreground">Select a timezone</span>
                    <ChevronDown class="w-4 h-4 text-icon-default shrink-0" />
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>
        <ComboboxList>
            <ComboboxInput
                v-model="searchValue"
                :display-value="emptySearch"
                auto-focus
                aria-label="Search timezones"
                placeholder="Search timezones..." />
            <ComboboxViewport class="p-1">
                <ComboboxVirtualizer
                    v-slot="{ option }"
                    :options="filteredTimezones"
                    :estimate-size="ROW_HEIGHT"
                    :text-content="timezoneName">
                    <ComboboxItem :value="option" class="justify-between gap-2">
                        <span class="truncate">{{ option }}</span>
                        <Check v-if="option === model" class="h-4 w-4 shrink-0" />
                    </ComboboxItem>
                </ComboboxVirtualizer>
            </ComboboxViewport>
            <div v-if="filteredTimezones.length === 0" class="px-3 py-2 text-sm text-text-tertiary">
                No timezone found.
            </div>
        </ComboboxList>
    </Combobox>
</template>
