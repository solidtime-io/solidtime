<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import { canViewReport } from '@/utils/permissions';
import { computed } from 'vue';
defineProps<{
    active: 'reporting' | 'detailed' | 'shared';
}>();

const showSharedReports = computed(() => canViewReport());
</script>

<template>
    <TabBar :model-value="active">
        <TabBarItem value="reporting" @click="router.visit(route('reporting'))"
            >Overview</TabBarItem
        >
        <TabBarItem value="detailed" @click="router.visit(route('reporting.detailed'))"
            >Detailed</TabBarItem
        >
        <TabBarItem
            v-if="showSharedReports"
            value="shared"
            @click="router.visit(route('reporting.shared'))"
            >Shared</TabBarItem
        >
    </TabBar>
</template>

<style scoped></style>
