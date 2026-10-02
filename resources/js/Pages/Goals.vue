<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Target } from '@lucide/vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { computed, ref } from 'vue';
import GoalTable from '@/Components/Common/Goal/GoalTable.vue';
import GoalCreateModal from '@/Components/Common/Goal/GoalCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import UpgradeBadge from '@/Components/Common/UpgradeBadge.vue';
import { canCreateGoals } from '@/utils/permissions';
import { canCreateMoreGoals, goalLimit } from '@/utils/billing';
import { useGoalsQuery } from '@/utils/useGoalsQuery';
import { TabBar, TabBarItem } from '@/packages/ui/src';

const showCreateGoalModal = ref(false);
const activeTab = ref('active');
const showArchived = computed(() => activeTab.value === 'archived');

// Archived goals count towards the goal limit, so the whole list is loaded and split here
const { goals, isLoading } = useGoalsQuery('all');
const visibleGoals = computed(() =>
    goals.value.filter((goal) => goal.is_archived === showArchived.value)
);
const archivedCount = computed(() => goals.value.filter((goal) => goal.is_archived).length);
const canCreateMore = computed(() => canCreateMoreGoals(goals.value.length));
const limit = goalLimit();
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
            <div class="flex items-center space-x-3">
                <UpgradeBadge v-if="canCreateGoals() && !canCreateMore">
                    <strong>More than {{ limit }} goal{{ limit === 1 ? '' : 's' }}</strong> is only
                    available in solidtime Professional.
                </UpgradeBadge>
                <SecondaryButton
                    v-if="canCreateGoals() && canCreateMore"
                    :icon="PlusIcon"
                    @click="showCreateGoalModal = true"
                    >Create Goal
                </SecondaryButton>
            </div>
            <GoalCreateModal v-model:show="showCreateGoalModal"></GoalCreateModal>
        </MainContainer>
        <GoalTable
            :goals="visibleGoals"
            :is-loading="isLoading"
            :can-create-more-goals="canCreateMore"
            :showing-archived="showArchived"></GoalTable>
    </AppLayout>
</template>
