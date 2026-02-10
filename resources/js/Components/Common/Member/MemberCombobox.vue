<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { UserIcon } from '@heroicons/vue/24/solid';
import { ChevronDown } from 'lucide-vue-next';
import type { ProjectMember } from '@/packages/api/src';
import type { Member } from '@/packages/api/src';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { Button } from '@/packages/ui/src/Buttons';

const { members } = useMembersQuery();

const model = defineModel<string>({
    default: '',
});

const props = withDefaults(
    defineProps<{
        hiddenMembers?: ProjectMember[];
        disabled?: boolean;
    }>(),
    {
        hiddenMembers: () => [] as ProjectMember[],
        disabled: false,
    }
);

const open = ref(false);
const searchValue = ref('');
const searchInput = ref<HTMLElement | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        searchValue.value = '';
        nextTick(() => {
            // @ts-expect-error We need to access the actual HTML Element to focus
            searchInput.value?.$el?.focus();
        });
    }
});

const filteredMembers = computed<Member[]>(() => {
    return members.value.filter((member) => {
        return (
            member.name.toLowerCase().includes(searchValue.value.toLowerCase().trim() || '') &&
            !props.hiddenMembers.some((hiddenMember) => hiddenMember.member_id === member.id) &&
            member.is_placeholder === false
        );
    });
});

const currentValue = computed(() => {
    if (model.value) {
        return members.value.find((member) => member.id === model.value)?.name;
    }
    return '';
});

function selectMember(member: Member) {
    model.value = member.id;
    open.value = false;
}
</script>

<template>
    <Dropdown v-model="open" align="start" :close-on-content-click="false">
        <template #trigger>
            <Button
                :disabled="disabled"
                type="button"
                variant="input"
                class="w-full justify-between text-start font-normal">
                <div class="flex items-center gap-3 truncate">
                    <UserIcon class="w-4 text-text-secondary shrink-0" />
                    <span v-if="currentValue" class="truncate text-text-primary">{{
                        currentValue
                    }}</span>
                    <span v-else class="text-muted-foreground">Select a member...</span>
                </div>
                <ChevronDown class="w-4 h-4 text-icon-default shrink-0" />
            </Button>
        </template>
        <template #content>
            <ComboboxRoot
                v-model:search-term="searchValue"
                v-model:open="open"
                class="relative"
                :filter-function="(val: string[]) => val">
                <ComboboxAnchor>
                    <ComboboxInput
                        ref="searchInput"
                        class="bg-card-background border-0 placeholder-text-tertiary text-sm text-text-primary py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                        placeholder="Search for a member..." />
                </ComboboxAnchor>
                <ComboboxContent
                    :dismiss-able="false"
                    position="inline"
                    class="w-60 max-h-60 overflow-y-auto">
                    <ComboboxViewport>
                        <ComboboxItem
                            v-for="member in filteredMembers"
                            :key="member.id"
                            :value="member.id"
                            class="flex items-center gap-3 px-3 py-2.5 text-sm text-text-primary data-[highlighted]:bg-card-background-active cursor-default"
                            @select.prevent="selectMember(member)">
                            <UserIcon class="w-4 text-text-secondary shrink-0" />
                            <span class="truncate">{{ member.name }}</span>
                        </ComboboxItem>
                    </ComboboxViewport>
                </ComboboxContent>
            </ComboboxRoot>
        </template>
    </Dropdown>
</template>

<style scoped></style>
