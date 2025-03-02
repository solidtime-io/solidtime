<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
defineProps<{
    active: 'reporting' | 'detailed' | 'shared';
}>();
</script>

<template>
    <TabBar>
        <TabBarItem
            :active="active === 'reporting'"
            @click="router.visit(route('reporting'))"
            >Overview</TabBarItem
        >
        <TabBarItem
            :active="active === 'detailed'"
            @click="router.visit(route('reporting.detailed'))"
            >Detailed</TabBarItem
        >
        <TabBarItem
            :active="active === 'shared'"
            @click="router.visit(route('reporting.shared'))"
            >Shared</TabBarItem
        >
    </TabBar>
</template>

<style scoped></style>
