<script setup lang="ts">
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import Badge from '@/packages/ui/src/Badge.vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import type { ProjectMemberRole } from '@/utils/useProjectMembers';

type ProjectMemberRoleItem = { key: ProjectMemberRole; name: string };

const projectMemberRoles: ProjectMemberRoleItem[] = [
    {
        key: 'normal',
        name: 'Normal',
    },
    {
        key: 'manager',
        name: 'Manager',
    },
];

const model = defineModel<string>({
    default: 'normal',
});

function getKeyFromItem(item: ProjectMemberRoleItem) {
    return item.key;
}

function getNameFromItem(item: ProjectMemberRoleItem) {
    return item.name;
}

function getNameForKey(key: string | undefined) {
    return projectMemberRoles.find((item) => item.key === key)?.name ?? '';
}
</script>

<template>
    <SelectDropdown
        v-model="model"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameFromItem"
        :items="projectMemberRoles">
        <template #trigger>
            <Badge size="xlarge" class="bg-input-background cursor-pointer">
                <span>
                    {{ getNameForKey(model) }}
                </span>
                <ChevronDownIcon class="text-muted w-5"></ChevronDownIcon>
            </Badge>
        </template>
    </SelectDropdown>
</template>

<style scoped></style>
