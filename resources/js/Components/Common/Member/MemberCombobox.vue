<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import { useMembersStore } from '@/utils/useMembers';
import { UserIcon, ChevronDownIcon } from '@heroicons/vue/24/solid';
import { useFocus } from '@vueuse/core';
import type { ProjectMember } from '@/packages/api/src';
import { Badge, SelectDropdown } from '@/packages/ui/src';
import type { Member } from '@/packages/api/src';

const membersStore = useMembersStore();
const { members } = storeToRefs(membersStore);

const model = defineModel<string>({
    default: '',
});

const props = withDefaults(
    defineProps<{
        hiddenMembers: ProjectMember[];
        disabled: boolean;
    }>(),
    {
        hiddenMembers: () => [] as ProjectMember[],
        disabled: false,
    }
);

const searchInput = ref<HTMLInputElement | null>(null);

const searchValue = ref('');

useFocus(searchInput, { initialValue: true });

const filteredMembers = computed<Member[]>(() => {
    return members.value.filter((member) => {
        return (
            member.name
                .toLowerCase()
                .includes(searchValue.value?.toLowerCase()?.trim() || '') &&
            !props.hiddenMembers.some(
                (hiddenMember) => hiddenMember.member_id === member.id
            ) &&
            member.is_placeholder === false
        );
    });
});

watch(filteredMembers, () => {
    resetHighlightedItem();
});

onMounted(() => {
    resetHighlightedItem();
});

function resetHighlightedItem() {
    if (filteredMembers.value.length > 0) {
        highlightedItemId.value = filteredMembers.value[0].id;
    }
}

const highlightedItemId = ref<string | null>(null);

const currentValue = computed(() => {
    if (model.value) {
        return members.value.find((member) => member.id === model.value)?.name;
    }
    return searchValue.value;
});
</script>

<template>
    <SelectDropdown
        v-model="model"
        :items="filteredMembers"
        :get-key-from-item="(member) => member.id"
        :get-name-for-item="(member) => member.name">
        <template v-slot:trigger>
            <Badge
                tag="button"
                class="flex w-full text-base text-left space-x-3 px-3 text-text-secondary font-normal cursor py-1.5">
                <UserIcon class="relative z-10 w-4 text-muted"></UserIcon>
                <div v-if="currentValue" class="flex-1 truncate">
                    {{ currentValue }}
                </div>
                <div class="flex-1" v-else>Select a member...</div>
                <ChevronDownIcon class="w-4 text-muted"></ChevronDownIcon>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
