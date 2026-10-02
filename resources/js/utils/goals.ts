import type { Goal, GoalComparison, GoalPeriod, GoalStatus } from '@/packages/api/src';
import { canDeleteOrganizationGoals, canUpdateOrganizationGoals } from '@/utils/permissions';
import { getCurrentMembershipId } from '@/utils/useUser';

export const goalComparisonOptions: { value: GoalComparison; label: string }[] = [
    { value: 'at_least', label: 'At least' },
    { value: 'less_than', label: 'Less than' },
];

export const goalPeriodOptions: { value: GoalPeriod; label: string }[] = [
    { value: 'day', label: 'per day' },
    { value: 'week', label: 'per week' },
    { value: 'month', label: 'per month' },
];

/**
 * Who a goal is for. The three answers map to the goal type and the member of the goal:
 * me = a personal goal for yourself, other = an organization goal for one member,
 * organization = an organization goal that counts every member.
 */
export type GoalTarget = 'me' | 'other' | 'organization';

export const goalTargetOptions: { value: GoalTarget; label: string }[] = [
    { value: 'me', label: 'For me' },
    { value: 'other', label: 'For someone else' },
    { value: 'organization', label: 'Organization goal' },
];

/**
 * Mirrors the access rules on the server: a personal goal belongs to its member, an organization goal
 * to the members that may manage goals. The member an organization goal is for never manages it.
 */
export function canManageGoal(goal: Goal): boolean {
    if (goal.type === 'personal') {
        return goal.member_id === getCurrentMembershipId();
    }
    return canUpdateOrganizationGoals() || canDeleteOrganizationGoals();
}

export function goalTypeLabel(goal: Goal): string {
    return goal.type === 'personal' ? 'Personal goal' : 'Organization goal';
}

export function goalComparisonLabel(comparison: GoalComparison): string {
    return goalComparisonOptions.find((option) => option.value === comparison)?.label ?? comparison;
}

export function goalStatusLabel(status: GoalStatus): string {
    switch (status) {
        case 'in_progress':
            return 'In progress';
        case 'achieved':
            return 'Achieved';
        case 'on_track':
            return 'On track';
        case 'exceeded':
            return 'Exceeded';
    }
}

export function goalStatusIsPositive(status: GoalStatus): boolean {
    return status === 'achieved' || status === 'on_track';
}
