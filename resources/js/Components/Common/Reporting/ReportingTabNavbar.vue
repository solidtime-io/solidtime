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
            @click="router.visit(route('reporting'))"
            :active="active === 'reporting'"
            >Overview</TabBarItem
        >
        <TabBarItem
            @click="router.visit(route('reporting.detailed'))"
            :active="active === 'detailed'"
            >Detailed</TabBarItem
        >
        <TabBarItem
            @click="router.visit(route('reporting.shared'))"
            :active="active === 'shared'"
            >Shared</TabBarItem
        >
    </TabBar>
</template>

<style scoped></style>
