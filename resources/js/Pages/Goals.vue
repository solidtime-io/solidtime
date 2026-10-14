<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Target } from '@lucide/vue';
import { computed, ref } from 'vue';
import GoalTable from '@/Components/Common/Goal/GoalTable.vue';
import GoalCreateButton from '@/Components/Common/Goal/GoalCreateButton.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateGoals } from '@/utils/permissions';
import { canCreateMoreGoals } from '@/utils/billing';
import { useGoalsQuery } from '@/utils/useGoalsQuery';
import { TabBar, TabBarItem } from '@/packages/ui/src';

const activeTab = ref('active');
const showArchived = computed(() => activeTab.value === 'archived');

// Archived goals count towards the goal limit, so the whole list is loaded and split here
const { goals, isLoading } = useGoalsQuery('all');
const visibleGoals = computed(() =>
    goals.value.filter((goal) => goal.is_archived === showArchived.value)
);
const archivedCount = computed(() => goals.value.filter((goal) => goal.is_archived).length);
const canCreateMore = computed(() => canCreateMoreGoals(goals.value.length));
</script>

<template>
    <AppLayout title="Goals" data-testid="goals_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <PageTitle :icon="Target" title="Goals"></PageTitle>
                <TabBar v-model="activeTab">
                    <TabBarItem value="active">Active</TabBarItem>
                    <TabBarItem value="archived">
                        Archived
                        <span v-if="archivedCount > 0" class="pl-1">({{ archivedCount }})</span>
                    </TabBarItem>
                </TabBar>
            </div>
            <GoalCreateButton
                v-if="canCreateGoals()"
                :can-create-more-goals="canCreateMore"></GoalCreateButton>
        </MainContainer>
        <GoalTable
            :goals="visibleGoals"
            :is-loading="isLoading"
            :can-create-more-goals="canCreateMore"
            :showing-archived="showArchived"></GoalTable>
    </AppLayout>
</template>
