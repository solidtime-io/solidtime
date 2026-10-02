<script setup lang="ts">
import { computed } from 'vue';
import type { Goal } from '@/packages/api/src';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { canViewMembers } from '@/utils/permissions';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/packages/ui/src';
import { FolderIcon } from '@heroicons/vue/16/solid';
import { CheckCircleIcon, TagIcon, UserCircleIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { Coffee } from '@lucide/vue';

const props = defineProps<{
    goal: Goal;
}>();

const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();
const { tags } = useTagsQuery();
// Only goals that count every member can be narrowed down to a subset of the members
const { members } = useMembersQuery({ enabled: canViewMembers });

function names(
    ids: string[] | null,
    items: { id: string; name: string }[],
    noneLabel: string
): { id: string; name: string }[] {
    if (ids === null) {
        return [];
    }
    return ids.map((id) => {
        if (id === 'none') {
            return { id, name: noneLabel };
        }
        return { id, name: items.find((item) => item.id === id)?.name ?? 'Unknown' };
    });
}

const badges = computed(() => {
    const filters = props.goal.filters;
    const notPrefix = filters.tag_match_type === 'not_contains' ? 'Not ' : '';
    // Fixed display order: members, projects, tasks, clients, tags
    const sources = [
        {
            kind: 'member',
            ids: filters.member_ids,
            items: members.value,
            none: 'Unknown member',
            icon: UserGroupIcon,
            prefix: '',
        },
        {
            kind: 'project',
            ids: filters.project_ids,
            items: projects.value,
            none: 'No project',
            icon: FolderIcon,
            prefix: '',
        },
        {
            kind: 'task',
            ids: filters.task_ids,
            items: tasks.value,
            none: 'No task',
            icon: CheckCircleIcon,
            prefix: '',
        },
        {
            kind: 'client',
            ids: filters.client_ids,
            items: clients.value,
            none: 'No client',
            icon: UserCircleIcon,
            prefix: '',
        },
        {
            kind: 'tag',
            ids: filters.tag_ids,
            items: tags.value,
            none: 'No tag',
            icon: TagIcon,
            prefix: notPrefix,
        },
    ];
    const result: { key: string; icon: unknown; label: string }[] = sources.flatMap((source) =>
        names(source.ids, source.items, source.none).map((entry) => ({
            key: source.kind + '-' + entry.id,
            icon: source.icon,
            label: source.prefix + entry.name,
        }))
    );
    if (filters.billable !== null) {
        result.push({
            key: 'billable',
            icon: BillableIcon,
            label: filters.billable ? 'Billable' : 'Non billable',
        });
    }
    if (filters.time_entry_type === 'break') {
        result.push({ key: 'type', icon: Coffee, label: 'Breaks' });
    }
    return result;
});

// The list keeps every row on a single line, the first badge is shown and all of them are listed on hover
const firstBadge = computed(() => badges.value[0] ?? null);
const hiddenBadges = computed(() => badges.value.slice(1));
</script>

<template>
    <div class="flex items-center min-w-0 text-sm" data-testid="goal_scope">
        <span v-if="firstBadge === null" class="text-text-secondary whitespace-nowrap"
            >All time entries</span
        >
        <TooltipProvider v-else :delay-duration="100">
            <Tooltip :disabled="hiddenBadges.length === 0">
                <TooltipTrigger as-child>
                    <span class="flex items-center gap-1.5 max-w-full min-w-0 cursor-default">
                        <component
                            :is="firstBadge.icon"
                            class="w-3.5 h-3.5 text-icon-default shrink-0"></component>
                        <span class="truncate">{{ firstBadge.label }}</span>
                        <span v-if="hiddenBadges.length > 0" class="text-text-secondary shrink-0"
                            >+{{ hiddenBadges.length }}</span
                        >
                    </span>
                </TooltipTrigger>
                <TooltipContent class="min-w-48 px-3 py-2">
                    <div class="flex flex-col gap-1.5">
                        <span
                            v-for="badge in badges"
                            :key="badge.key"
                            class="flex items-center gap-1.5">
                            <component
                                :is="badge.icon"
                                class="w-3.5 h-3.5 text-icon-default shrink-0"></component>
                            <span>{{ badge.label }}</span>
                        </span>
                    </div>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>

<style scoped></style>
