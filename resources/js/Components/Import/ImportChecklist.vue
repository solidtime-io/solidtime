<script setup lang="ts">
import { computed } from 'vue';
import type { ImportListItem } from './importSources';
import ImportLinkButton from './ImportLinkButton.vue';
import ImportText from './ImportText.vue';

const props = defineProps<{
    items: (string | ImportListItem)[];
}>();

const normalizedItems = computed<ImportListItem[]>(() =>
    props.items.map((item) => (typeof item === 'string' ? { text: item } : item))
);
// Lists with previews get more room between the items
const hasPreviews = computed(() => normalizedItems.value.some((item) => item.preview));
</script>

<template>
    <ol :class="hasPreviews ? 'space-y-5' : 'space-y-2'">
        <li v-for="(item, index) in normalizedItems" :key="item.text" class="flex gap-2.5">
            <span
                class="flex-none h-5 w-5 rounded-full border border-card-border bg-card-background-active text-xs font-semibold text-text-primary flex items-center justify-center"
                >{{ index + 1 }}</span
            >
            <div class="min-w-0 flex-1 space-y-2">
                <p class="max-w-md text-text-primary">
                    <ImportText :text="item.text" />
                </p>
                <div v-if="item.preview" class="max-w-md">
                    <component :is="item.preview" />
                </div>
                <ImportLinkButton v-if="item.link" v-bind="item.link" />
            </div>
        </li>
    </ol>
</template>
