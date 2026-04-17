<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldLabel, FieldDescription } from '@/packages/ui/src/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/packages/ui/src';
import { Checkbox } from '@/packages/ui/src';
import { usePreferredColorScheme } from '@vueuse/core';
import { themeSetting } from '@/utils/theme';
import { groupSimilarTimeEntriesSetting } from '@/utils/timeEntryGrouping';

const preferredColor = usePreferredColorScheme();
</script>

<template>
    <FormSection>
        <template #title> Theme</template>

        <template #description> Choose how you want solidtime to look on your device </template>

        <template #form>
            <!-- Theme -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="theme">Theme</FieldLabel>
                <Select id="theme" v-model="themeSetting">
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="system">System</SelectItem>
                        <SelectItem value="light">Light</SelectItem>
                        <SelectItem value="dark">Dark</SelectItem>
                    </SelectContent>
                </Select>
                <FieldDescription v-if="themeSetting === 'system'">
                    System default: {{ preferredColor }}
                </FieldDescription>
            </Field>

            <!-- Group similar time entries -->
            <Field class="col-span-6 sm:col-span-4" orientation="horizontal">
                <Checkbox
                    id="group_similar_time_entries"
                    v-model:checked="groupSimilarTimeEntriesSetting" />
                <FieldLabel for="group_similar_time_entries">Group similar time entries</FieldLabel>
            </Field>
        </template>
    </FormSection>
</template>
