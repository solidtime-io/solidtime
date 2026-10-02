<script setup lang="ts">
import type { Goal, Organization } from '@/packages/api/src';
import { computed, inject, ref, type ComputedRef } from 'vue';
import { ArchiveBoxIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid';
import TableRow from '@/Components/TableRow.vue';
import GoalMoreOptionsDropdown from '@/Components/Common/Goal/GoalMoreOptionsDropdown.vue';
import GoalEditModal from '@/Components/Common/Goal/GoalEditModal.vue';
import GoalProgressBar from '@/Components/Common/Goal/GoalProgressBar.vue';
import GoalScopeBadges from '@/Components/Common/Goal/GoalScopeBadges.vue';
import GoalStatusBadge from '@/Components/Common/Goal/GoalStatusBadge.vue';
import { useGoalsStore } from '@/utils/useGoals';
import { canDeleteGoals, canUpdateGoals } from '@/utils/permissions';
import {
    canManageGoal,
    goalComparisonLabel,
    goalPeriodOptions,
    goalTypeLabel,
} from '@/utils/goals';
import { Badge } from '@/packages/ui/src';
import { getCurrentMembershipId } from '@/utils/useUser';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/packages/ui/src';

const props = defineProps<{
    goal: Goal;
    showTargetColumn: boolean;
}>();

const organization = inject<ComputedRef<Organization>>('organization');
const showEditModal = ref(false);
// A personal goal is managed by its member, an organization goal by the members that may manage goals
const isManager = computed(() => canManageGoal(props.goal));
const canEdit = computed(() => isManager.value && canUpdateGoals());
const canDelete = computed(() => isManager.value && canDeleteGoals());
const targetLabel = computed(() => {
    if (props.goal.member_id === null) return 'Every member';
    if (props.goal.member_id === getCurrentMembershipId()) return 'You';
    return props.goal.member_name ?? 'Member';
});
const typeDescription = computed(() => goalTypeLabel(props.goal));

const targetDescription = computed(() => {
    const period = goalPeriodOptions.find((option) => option.value === props.goal.period)?.label;
    return (
        goalComparisonLabel(props.goal.comparison) +
        ' ' +
        formatHumanReadableDuration(
            props.goal.target_seconds,
            organization?.value?.interval_format,
            organization?.value?.number_format
        ) +
        ' ' +
        period
    );
});

function deleteGoal() {
    useGoalsStore().deleteGoal(props.goal.id);
}

function toggleArchived() {
    useGoalsStore().setGoalArchived(props.goal.id, !props.goal.is_archived);
}
</script>

<template>
    <ContextMenu>
        <ContextMenuTrigger as-child>
            <TableRow :data-testid="'goal_row_' + goal.id">
                <div
                    class="whitespace-nowrap min-w-0 flex items-center gap-2 py-4 pr-3 text-sm pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                    <span
                        class="font-medium text-text-primary overflow-ellipsis overflow-hidden"
                        data-testid="goal_name"
                        >{{ goal.name }}</span
                    >
                    <Badge v-if="goal.is_archived" size="base" data-testid="goal_archived_badge"
                        >Archived</Badge
                    >
                </div>
                <div
                    v-if="showTargetColumn"
                    class="min-w-0 flex items-center px-3 py-4 text-sm text-text-primary"
                    data-testid="goal_target">
                    <span class="sr-only">{{ typeDescription }}</span>
                    <span class="truncate">{{ targetLabel }}</span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary"
                    data-testid="goal_target_description">
                    {{ targetDescription }}
                </div>
                <!-- Like the progress in the project table, the cells without vertical padding keep the row as high as the other tables -->
                <div class="min-w-0 flex items-center px-3 text-sm text-text-primary">
                    <GoalScopeBadges :goal="goal"></GoalScopeBadges>
                </div>
                <div class="whitespace-nowrap flex items-center px-3 text-sm text-text-primary">
                    <GoalProgressBar :goal="goal" class="max-w-44"></GoalProgressBar>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <GoalStatusBadge :status="goal.progress.status"></GoalStatusBadge>
                </div>
                <div
                    class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
                    <GoalMoreOptionsDropdown
                        v-if="canDelete || canEdit"
                        :goal="goal"
                        :can-edit="canEdit"
                        :can-delete="canDelete"
                        @edit="showEditModal = true"
                        @archive="toggleArchived"
                        @delete="deleteGoal"></GoalMoreOptionsDropdown>
                </div>
                <GoalEditModal v-model:show="showEditModal" :goal="goal"></GoalEditModal>
            </TableRow>
        </ContextMenuTrigger>
        <ContextMenuContent class="min-w-[160px]">
            <ContextMenuItem v-if="canEdit" class="space-x-3" @select="showEditModal = true">
                <PencilSquareIcon class="w-4 h-4 text-icon-default" />
                <span>Edit</span>
            </ContextMenuItem>
            <ContextMenuItem v-if="canEdit" class="space-x-3" @select="toggleArchived()">
                <ArchiveBoxIcon class="w-4 h-4 text-icon-default" />
                <span>{{ goal.is_archived ? 'Unarchive' : 'Archive' }}</span>
            </ContextMenuItem>
            <ContextMenuSeparator v-if="canDelete" />
            <ContextMenuItem
                v-if="canDelete"
                class="space-x-3 text-destructive"
                @select="deleteGoal()">
                <TrashIcon class="w-4 h-4 text-icon-default" />
                <span>Delete</span>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<style scoped></style>
