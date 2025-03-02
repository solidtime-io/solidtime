<script setup lang="ts">
import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import { useMembersStore } from '@/utils/useMembers';
import { storeToRefs } from 'pinia';
import type { Member } from '@/packages/api/src';

const membersStore = useMembersStore();
const { members } = storeToRefs(membersStore);

function getKeyFromItem(item: Member) {
    return item.id;
}

function getNameForItem(item: Member) {
    return item.name;
}
</script>

<template>
    <MultiselectDropdown
        search-placeholder="Search for a Member..."
        :items="members"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameForItem">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
    </MultiselectDropdown>
</template>
