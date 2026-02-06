<script setup lang="ts">
import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import { useMembersQuery } from '@/utils/useMembersQuery';
import type { Member } from '@/packages/api/src';

const { members } = useMembersQuery();

function getKeyFromItem(item: Member) {
    return item.id;
}

function getNameForItem(item: Member) {
    return item.name;
}

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <MultiselectDropdown
        search-placeholder="Search for a Member..."
        :items="members"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameForItem"
        @submit="emit('submit')">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
    </MultiselectDropdown>
</template>
