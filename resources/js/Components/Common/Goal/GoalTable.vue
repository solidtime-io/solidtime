<script setup lang="ts">
import { Target } from '@lucide/vue';
import type { Goal } from '@/packages/api/src';
import GoalTableRow from '@/Components/Common/Goal/GoalTableRow.vue';
import GoalTableHeading from '@/Components/Common/Goal/GoalTableHeading.vue';
import GoalCreateButton from '@/Components/Common/Goal/GoalCreateButton.vue';
import { canCreateGoals } from '@/utils/permissions';
import { LoadingSpinner } from '@/packages/ui/src';
import { isGoalsExtensionActivated } from '@/utils/billing';

const props = defineProps<{
    goals: Goal[];
    isLoading: boolean;
    canCreateMoreGoals: boolean;
    showingArchived: boolean;
}>();

const showTargetColumn = isGoalsExtensionActivated();
// Every column gets a share of the free space so the columns are spread evenly over the table
const gridTemplateColumns = [
    'minmax(180px, 1.25fr)',
    ...(showTargetColumn ? ['minmax(120px, 0.75fr)'] : []),
    'minmax(200px, 1fr)',
    'minmax(180px, 1fr)',
    'minmax(160px, 1fr)',
    'minmax(120px, 0.75fr)',
    '80px',
].join(' ');
</script>

<template>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div data-testid="goal_table" class="grid min-w-full" :style="{ gridTemplateColumns }">
                <GoalTableHeading :show-target-column="showTargetColumn"></GoalTableHeading>
                <div
                    v-if="props.isLoading"
                    class="col-span-full flex justify-center items-center py-24">
                    <LoadingSpinner></LoadingSpinner>
                </div>
                <div v-else-if="props.goals.length === 0" class="col-span-full py-24 text-center">
                    <Target class="w-8 h-8 text-icon-default inline pb-2"></Target>
                    <h3 class="text-text-primary font-semibold">
                        {{ props.showingArchived ? 'No archived goals found' : 'No goals found' }}
                    </h3>
                    <p v-if="canCreateGoals() && !props.showingArchived" class="pb-5">
                        Set yourself a target and track your progress every day, week or month.
                    </p>
                    <GoalCreateButton
                        v-if="canCreateGoals() && !props.showingArchived"
                        :can-create-more-goals="props.canCreateMoreGoals"
                        >Create your first goal
                    </GoalCreateButton>
                </div>
                <template v-for="goal in props.goals" :key="goal.id">
                    <GoalTableRow
                        :goal="goal"
                        :show-target-column="showTargetColumn"></GoalTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
