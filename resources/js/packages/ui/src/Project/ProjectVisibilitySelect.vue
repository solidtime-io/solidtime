<script setup lang="ts">
import { computed } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '..';
import { Field, FieldDescription, FieldLabel } from '../field';
import { GlobeAltIcon } from '@heroicons/vue/20/solid';

const isPublic = defineModel<boolean>({ default: false });

const visibility = computed({
    get: () => (isPublic.value ? 'public' : 'private'),
    set: (value: string) => {
        isPublic.value = value === 'public';
    },
});

const description = computed(() =>
    isPublic.value
        ? 'This project is visible to all members of the organization.'
        : 'This project is only visible to its project members.'
);
</script>

<template>
    <Field>
        <FieldLabel :icon="GlobeAltIcon" for="visibility">Visibility</FieldLabel>
        <Select v-model="visibility">
            <SelectTrigger id="visibility">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="private">Private</SelectItem>
                <SelectItem value="public">Public</SelectItem>
            </SelectContent>
        </Select>
        <FieldDescription>{{ description }}</FieldDescription>
    </Field>
</template>

<style scoped></style>
