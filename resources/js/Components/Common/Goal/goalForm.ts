import type {
    CreateGoalBody,
    Goal,
    GoalComparison,
    GoalPeriod,
    GoalType,
    GoalWeekStart,
    UpdateGoalBody,
} from '@/packages/api/src';
import type { TagMatchType } from '@/types/reporting';
import type { GoalTarget } from '@/utils/goals';
import { getCurrentMembershipId, getCurrentUser } from '@/utils/useUser';

export interface GoalFormData {
    name: string;
    // Who the goal is for, this decides the type and the member of the goal
    target: GoalTarget;
    comparison: GoalComparison;
    target_seconds: number | null;
    period: GoalPeriod;
    // ID of the member the goal is for, only used for the target "other"
    other_member_id: string | null;
    timezone: string;
    week_start: GoalWeekStart;
    // Members whose time counts, only used for organization goals, empty = every member
    member_ids: string[];
    project_ids: string[];
    task_ids: string[];
    tag_ids: string[];
    tag_match_type: TagMatchType;
    client_ids: string[];
    billable: 'true' | 'false' | null;
    time_entry_type: 'work' | 'break' | null;
}

export function emptyGoalFormData(): GoalFormData {
    return {
        name: '',
        target: 'me',
        comparison: 'at_least',
        target_seconds: null,
        period: 'week',
        other_member_id: null,
        timezone: getCurrentUser().timezone,
        week_start: getCurrentUser().week_start as GoalWeekStart,
        member_ids: [],
        project_ids: [],
        task_ids: [],
        tag_ids: [],
        tag_match_type: 'contains',
        client_ids: [],
        billable: null,
        // Breaks are excluded by default, the user can widen the scope explicitly
        time_entry_type: 'work',
    };
}

export function goalTarget(goal: Goal): GoalTarget {
    if (goal.type === 'personal') {
        return 'me';
    }
    return goal.member_id === null ? 'organization' : 'other';
}

export function goalToFormData(goal: Goal): GoalFormData {
    const target = goalTarget(goal);
    return {
        name: goal.name,
        target,
        comparison: goal.comparison,
        target_seconds: goal.target_seconds,
        period: goal.period,
        other_member_id: target === 'other' ? goal.member_id : null,
        timezone: goal.timezone,
        week_start: goal.week_start,
        member_ids: goal.filters.member_ids ?? [],
        project_ids: goal.filters.project_ids ?? [],
        task_ids: goal.filters.task_ids ?? [],
        tag_ids: goal.filters.tag_ids ?? [],
        tag_match_type: goal.filters.tag_match_type ?? 'contains',
        client_ids: goal.filters.client_ids ?? [],
        billable: goal.filters.billable === null ? null : goal.filters.billable ? 'true' : 'false',
        time_entry_type: goal.filters.time_entry_type,
    };
}

export type FilterIdKey = 'member_ids' | 'project_ids' | 'task_ids' | 'tag_ids' | 'client_ids';

// Selectable IDs, or null when the list is unavailable. Missing IDs may still exist.
export type KnownFilterIds = Record<FilterIdKey, Set<string> | null>;

export interface UnavailableGoalFilter {
    key: FilterIdKey;
    label: string;
    ids: string[];
    removesRestriction: boolean;
}

const filterLabels: Record<FilterIdKey, string> = {
    member_ids: 'members',
    project_ids: 'projects',
    task_ids: 'tasks',
    tag_ids: 'tags',
    client_ids: 'clients',
};

export function getUnavailableGoalFilters(
    data: GoalFormData,
    known: KnownFilterIds
): UnavailableGoalFilter[] {
    return (Object.keys(known) as FilterIdKey[]).flatMap((key) => {
        const knownIds = known[key];
        if (knownIds === null) return [];
        const ids = data[key].filter((id) => id !== 'none' && !knownIds.has(id));
        return ids.length === 0
            ? []
            : [
                  {
                      key,
                      label: filterLabels[key],
                      ids,
                      removesRestriction: ids.length === data[key].length,
                  },
              ];
    });
}

// Remove only the IDs the user has reviewed and explicitly chosen to remove.
export function withoutUnavailableGoalFilters(
    data: GoalFormData,
    unavailable: UnavailableGoalFilter[]
): GoalFormData {
    const result = { ...data };
    for (const { key, ids } of unavailable) {
        result[key] = data[key].filter((id) => !ids.includes(id));
    }
    return result;
}

function idsOrNull(ids: string[]): string[] | null {
    return ids.length > 0 ? ids : null;
}

function goalType(target: GoalTarget): GoalType {
    return target === 'me' ? 'personal' : 'organization';
}

function goalMemberId(data: GoalFormData): string | null {
    if (data.target === 'me') {
        return getCurrentMembershipId() ?? null;
    }
    return data.target === 'other' ? data.other_member_id : null;
}

function goalFilters(data: GoalFormData) {
    return {
        // Only a goal that counts every member can be narrowed down to a subset of the members
        member_ids: data.target === 'organization' ? idsOrNull(data.member_ids) : null,
        project_ids: idsOrNull(data.project_ids),
        task_ids: idsOrNull(data.task_ids),
        tag_ids: idsOrNull(data.tag_ids),
        tag_match_type: data.tag_ids.length > 0 ? data.tag_match_type : null,
        client_ids: idsOrNull(data.client_ids),
        billable: data.billable === null ? null : data.billable === 'true',
        time_entry_type: data.time_entry_type,
    };
}

export function goalFormDataToCreateBody(data: GoalFormData): CreateGoalBody {
    return {
        name: data.name.trim(),
        type: goalType(data.target),
        comparison: data.comparison,
        target_seconds: data.target_seconds ?? 0,
        period: data.period,
        member_id: goalMemberId(data),
        timezone: data.timezone,
        week_start: data.week_start,
        filters: goalFilters(data),
    };
}

/**
 * The type of a goal and the member it is for can not be changed after creation.
 */
export function goalFormDataToUpdateBody(data: GoalFormData): UpdateGoalBody {
    return {
        name: data.name.trim(),
        comparison: data.comparison,
        target_seconds: data.target_seconds ?? 0,
        period: data.period,
        timezone: data.timezone,
        week_start: data.week_start,
        filters: goalFilters(data),
    };
}

export interface GoalFormErrors {
    name?: string;
    target_seconds?: string;
    other_member_id?: string;
    comparison?: string;
    period?: string;
    filters?: string;
    timezone?: string;
    week_start?: string;
}

/**
 * Maps the field errors of a 422 response (see getApiValidationFieldErrors) to the fields of the goal form.
 * Errors that no visible field can show (e.g. member_id of a goal for yourself) are returned as unmapped.
 */
export function goalFormErrorsFromApi(
    fieldErrors: Record<string, string>,
    data: GoalFormData
): { errors: GoalFormErrors; unmapped: string[] } {
    const errors: GoalFormErrors = {};
    const unmapped: string[] = [];
    const direct = [
        'name',
        'target_seconds',
        'comparison',
        'period',
        'timezone',
        'week_start',
    ] as const;
    for (const [field, message] of Object.entries(fieldErrors)) {
        const directField = direct.find((key) => key === field);
        if (directField !== undefined) {
            errors[directField] ??= message;
        } else if (field === 'member_id' && data.target === 'other') {
            errors.other_member_id ??= message;
        } else if (field === 'filters' || field.startsWith('filters.')) {
            errors.filters ??= message;
        } else {
            unmapped.push(message);
        }
    }
    return { errors, unmapped };
}

export function validateGoalFormData(data: GoalFormData): GoalFormErrors {
    const errors: GoalFormErrors = {};
    if (data.name.trim() === '') {
        errors.name = 'Please enter a name for the goal.';
    }
    if (data.target_seconds === null || data.target_seconds <= 0) {
        errors.target_seconds = 'Please enter a target time greater than zero.';
    }
    if (data.target === 'other' && data.other_member_id === null) {
        errors.other_member_id = 'Please choose the member this goal is for.';
    }
    return errors;
}
