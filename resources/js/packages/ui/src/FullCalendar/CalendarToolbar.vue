<script setup lang="ts">
import { Tabs, TabsList } from '../tabs';
import TabBarItem from '../TabBar/TabBarItem.vue';
import DateRangeNavigator from '../Input/DateRangeNavigator.vue';
import CalendarSettingsPopover from './CalendarSettingsPopover.vue';
import type { CalendarSettings } from './calendarSettings';

defineProps<{
    rangeLabel: string;
    activeView: string;
    settings: CalendarSettings;
}>();

const emit = defineEmits<{
    prev: [];
    next: [];
    today: [];
    'change-view': [view: string];
    'update:settings': [value: CalendarSettings];
}>();
</script>

<template>
    <div class="flex items-center justify-between bg-default-background px-2 py-1.5">
        <DateRangeNavigator
            :label="rangeLabel"
            trigger-test-id="calendar-title"
            trigger-aria-label="today"
            @previous="emit('prev')"
            @next="emit('next')"
            @select="emit('today')" />

        <!-- Right: View switcher + Settings -->
        <div class="flex items-center gap-1">
            <Tabs
                :model-value="activeView"
                @update:model-value="(v) => emit('change-view', String(v))">
                <TabsList class="flex items-center space-x-0.5 sm:space-x-1">
                    <TabBarItem value="timeGridWeek">week</TabBarItem>
                    <TabBarItem value="timeGridDay">day</TabBarItem>
                </TabsList>
            </Tabs>
            <CalendarSettingsPopover
                :settings="settings"
                @update:settings="(v) => emit('update:settings', v)" />
        </div>
    </div>
</template>
