<script setup lang="ts">
import { Button } from '..';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Tabs, TabsList } from '../tabs';
import TabBarItem from '../TabBar/TabBarItem.vue';
import CalendarSettingsPopover from './CalendarSettingsPopover.vue';
import type { CalendarSettings } from './calendarSettings';

defineProps<{
    viewTitle: string;
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
        <!-- Left: Navigation -->
        <div class="flex items-center gap-1">
            <Button
                variant="outline"
                size="sm"
                class="h-8 w-8 p-0"
                aria-label="Previous"
                @click="emit('prev')">
                <ChevronLeft class="h-4 w-4" />
            </Button>
            <Button
                variant="outline"
                size="sm"
                class="h-8 w-8 p-0"
                aria-label="Next"
                @click="emit('next')">
                <ChevronRight class="h-4 w-4" />
            </Button>
            <Button variant="outline" size="sm" @click="emit('today')"> today </Button>
        </div>

        <!-- Center: Title -->
        <span data-testid="calendar-title" class="text-base font-semibold text-foreground">{{
            viewTitle
        }}</span>

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
