<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { storeToRefs } from 'pinia';
import ClientDropdownItem from '@/Components/Common/Client/ClientDropdownItem.vue';
import { useMembersStore } from '@/utils/useMembers';
import { UserIcon, XMarkIcon } from '@heroicons/vue/24/solid';
import TextInput from '@/Components/TextInput.vue';
import { useFocus } from '@vueuse/core';
import type { ProjectMember } from '@/utils/api';
import Dropdown from '@/Components/Dropdown.vue';

const membersStore = useMembersStore();
const { members } = storeToRefs(membersStore);

const model = defineModel<string>({
    default: '',
});

const props = defineProps<{
    hiddenMembers: ProjectMember[];
}>();

const searchInput = ref<HTMLInputElement | null>(null);

const searchValue = ref('');

function isMemberSelected(id: string) {
    return model.value === id;
}

const { focused } = useFocus(searchInput, { initialValue: true });

const filteredMembers = computed(() => {
    return members.value.filter((member) => {
        return (
            member.name
                .toLowerCase()
                .includes(searchValue.value?.toLowerCase()?.trim() || '') &&
            !props.hiddenMembers.some(
                (hiddenMember) => hiddenMember.user_id === member.user_id
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
        highlightedItemId.value = filteredMembers.value[0].user_id;
    }
}

function updateSearchValue(event: Event) {
    const newInput = (event.target as HTMLInputElement).value;
    if (newInput === ' ') {
        searchValue.value = '';
        const highlightedClientId = highlightedItemId.value;
        if (highlightedClientId) {
            const highlightedClient = members.value.find(
                (member) => member.user_id === highlightedClientId
            );
            if (highlightedClient) {
                model.value = highlightedClient.user_id;
            }
        }
    } else {
        searchValue.value = newInput;
    }
}

const emit = defineEmits(['update:modelValue', 'changed']);

function updateMember(newValue: string | null) {
    console.log(newValue);
    if (newValue) {
        model.value = newValue;
        nextTick(() => {
            emit('changed');
        });
    }
}

function moveHighlightUp() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredMembers.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === 0) {
            highlightedItemId.value =
                filteredMembers.value[filteredMembers.value.length - 1].user_id;
        } else {
            highlightedItemId.value =
                filteredMembers.value[currentHightlightedIndex - 1].user_id;
        }
    }
}

function moveHighlightDown() {
    if (highlightedItem.value) {
        const currentHightlightedIndex = filteredMembers.value.indexOf(
            highlightedItem.value
        );
        if (currentHightlightedIndex === filteredMembers.value.length - 1) {
            highlightedItemId.value = filteredMembers.value[0].user_id;
        } else {
            highlightedItemId.value =
                filteredMembers.value[currentHightlightedIndex + 1].user_id;
        }
    }
}

const highlightedItemId = ref<string | null>(null);
const highlightedItem = computed(() => {
    return members.value.find(
        (member) => member.user_id === highlightedItemId.value
    );
});

const currentValue = computed(() => {
    if (model.value) {
        return members.value.find((member) => member.user_id === model.value)
            ?.name;
    }
    return searchValue.value;
});

const hasMemberSelected = computed(() => {
    return model.value !== '';
});

const showMembersDropdown = ref(true);

function onUnfocus() {
    // TODO this is a hack to prevent the dropdown from closing when clicking on the dropdown
    setTimeout(() => {
        if (!focused.value) {
            showMembersDropdown.value = false;
        }
    }, 100);
}
</script>

<template>
    <div class="flex relative">
        <div
            ref="reference"
            class="absolute h-full items-center px-3 w-full flex justify-between">
            <UserIcon class="relative z-10 w-4 text-muted"></UserIcon>
            <button
                v-if="hasMemberSelected"
                @click="model = ''"
                class="focus:text-accent-200 focus:bg-card-background text-muted">
                <XMarkIcon class="relative z-10 w-4"></XMarkIcon>
            </button>
        </div>
        <TextInput
            :value="currentValue"
            @input="updateSearchValue"
            data-testid="member_dropdown_search"
            @keydown.enter.prevent="updateMember(highlightedItemId)"
            @keydown.up.prevent="moveHighlightUp"
            class="relative w-full pl-10"
            @keydown.down.prevent="moveHighlightDown"
            @focusin="showMembersDropdown = true"
            @blur="onUnfocus"
            placeholder="Search for a member..."
            ref="searchInput" />
    </div>
    <Dropdown
        align="bottom-start"
        width="300"
        v-model="showMembersDropdown"
        :closeOnContentClick="true">
        <template #content>
            <div
                class="py-2 text-white px-3"
                v-if="filteredMembers.length === 0">
                All members are already added.
            </div>
            <div
                v-for="member in filteredMembers"
                :key="member.user_id"
                role="option"
                :value="member.user_id"
                :class="{
                    'bg-card-background-active':
                        member.user_id === highlightedItemId,
                }"
                @click="updateMember(member.user_id)"
                data-testid="client_dropdown_entries"
                :data-client-id="member.user_id">
                <ClientDropdownItem
                    :selected="isMemberSelected(member.user_id)"
                    :name="member.name"></ClientDropdownItem>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
