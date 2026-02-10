<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { canViewReport } from '@/utils/permissions';
import { computed } from 'vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';

const props = defineProps<{
    active: 'reporting' | 'detailed' | 'shared';
}>();

const showSharedReports = computed(() => canViewReport());

const tabs = computed(() => {
    const items = [
        { value: 'reporting', label: 'Overview', href: route('reporting') },
        { value: 'detailed', label: 'Detailed', href: route('reporting.detailed') },
    ];
    if (showSharedReports.value) {
        items.push({
            value: 'shared',
            label: 'Shared',
            href: route('reporting.shared'),
        });
    }
    return items;
});

function hrefForTab(value: string) {
    return tabs.value.find((tab) => tab.value === value)?.href;
}

function onTabChange(value: string | number) {
    const href = hrefForTab(String(value));
    if (href) {
        router.visit(href);
    }
}

function onTabHover(value: string) {
    const href = hrefForTab(value);
    if (href) {
        router.prefetch(href, {}, { cacheFor: '1m' });
    }
}
</script>

<template>
    <TabBar :default-value="props.active" @update:model-value="onTabChange">
        <TabBarItem
            v-for="tab in tabs"
            :key="tab.value"
            :value="tab.value"
            @mouseenter="onTabHover(tab.value)">
            {{ tab.label }}
        </TabBarItem>
    </TabBar>
</template>
