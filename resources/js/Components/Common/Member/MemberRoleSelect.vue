<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import type { Role } from '@/types/jetstream';
import { usePage } from '@inertiajs/vue3';

const model = defineModel<string>({
    default: 'employee',
});

const page = usePage<{
    availableRoles: Role[];
}>();

function getNameForKey(key: string | undefined) {
    const item = page.props.availableRoles.find((item) => item.key === key);
    if (item) {
        return item.name;
    }
    return '';
}
</script>

<template>
    <Select v-model="model">
        <SelectTrigger>
            <SelectValue>{{ getNameForKey(model) }}</SelectValue>
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-for="role in page.props.availableRoles" :key="role.key" :value="role.key">
                {{ role.name }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>

<style scoped></style>
