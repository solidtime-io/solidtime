<script setup lang="ts">
import { computed } from 'vue';
import VacationRequestStatusBadge from './VacationRequestStatusBadge.vue';
import { useVacationRequestMutations, type VacationRequest } from '@/utils/useVacationRequests';
import { canManageVacationRequests } from '@/utils/permissions';

const props = defineProps<{ requests: VacationRequest[]; loading: boolean }>();

const { updateStatus } = useVacationRequestMutations();
const canManage = computed(() => canManageVacationRequests());

const typeLabels: Record<string, string> = {
    regular_vacation: 'Regular vacation day',
    sick_day: 'Sick day',
    work_outside: 'Work outside office',
    special: 'Special leave',
};

const MONTH_ABBR = [
    'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN',
    'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC',
];

const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function formatDateRange(req: VacationRequest) {
    const start = new Date(req.start_date);
    const end = new Date(req.end_date);

    const startDay = DAY_NAMES[start.getDay()];
    const endDay = DAY_NAMES[end.getDay()];
    const startMonth = start.toLocaleString('en', { month: 'long' });
    const endMonth = end.toLocaleString('en', { month: 'long' });

    if (req.start_date === req.end_date) {
        return `${startDay} ${start.getDate()}. ${startMonth}`;
    }
    if (start.getMonth() === end.getMonth()) {
        return `${startDay} ${start.getDate()}. – ${endDay} ${end.getDate()}. ${startMonth}`;
    }
    return `${startDay} ${start.getDate()}. ${startMonth} – ${endDay} ${end.getDate()}. ${endMonth}`;
}

function getCalIconData(req: VacationRequest) {
    const start = new Date(req.start_date);
    return { month: MONTH_ABBR[start.getMonth()], day: start.getDate() };
}

function iconBgColor(req: VacationRequest) {
    if (req.status === 'withdrawn' || req.status === 'rejected')
        return 'bg-default-background border border-input-border text-text-quaternary';
    switch (req.type) {
        case 'regular_vacation': return 'bg-emerald-500 text-white';
        case 'sick_day': return 'bg-blue-500 text-white';
        case 'work_outside': return 'bg-yellow-500 text-white';
        default: return 'bg-purple-500 text-white';
    }
}

function dayLabel(req: VacationRequest) {
    const n = req.days_count;
    const type = typeLabels[req.type] ?? 'vacation day';
    if (req.half_day) return `½ ${type}`;
    return `${n} ${type}${n === 1 ? '' : 's'}`;
}

async function approve(req: VacationRequest) {
    await updateStatus.mutateAsync({ id: req.id, status: 'approved' });
}

async function reject(req: VacationRequest) {
    await updateStatus.mutateAsync({ id: req.id, status: 'rejected' });
}

async function withdraw(req: VacationRequest) {
    await updateStatus.mutateAsync({ id: req.id, status: 'withdrawn' });
}
</script>

<template>
    <div>
        <div v-if="loading" class="p-8 text-center text-text-tertiary text-sm">
            Loading absence requests...
        </div>

        <div
            v-else-if="requests.length === 0"
            class="p-12 text-center text-text-tertiary text-sm">
            No absence requests found.
        </div>

        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-default-background-separator">
                    <th class="text-left px-4 py-3 text-text-tertiary font-medium text-xs">
                        Period
                    </th>
                    <th class="text-left px-4 py-3 text-text-tertiary font-medium text-xs">
                        Status
                    </th>
                    <th class="text-left px-4 py-3 text-text-tertiary font-medium text-xs">
                        Notes
                    </th>
                    <th
                        v-if="canManage"
                        class="text-right px-4 py-3 text-text-tertiary font-medium text-xs">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="req in requests"
                    :key="req.id"
                    class="border-b border-default-background-separator last:border-0 hover:bg-tertiary/30 transition">
                    <!-- Period -->
                    <td class="px-4 py-3">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex-shrink-0 w-11 rounded-md overflow-hidden text-center text-xs font-bold"
                                :class="iconBgColor(req)">
                                <div class="text-[9px] uppercase tracking-wider py-0.5 opacity-80">
                                    {{ getCalIconData(req).month }}
                                </div>
                                <div class="text-lg leading-tight pb-1">
                                    {{ getCalIconData(req).day }}
                                </div>
                            </div>
                            <div>
                                <div class="text-text-primary font-medium">
                                    {{ formatDateRange(req) }}
                                </div>
                                <div class="text-text-tertiary text-xs mt-0.5">
                                    {{ dayLabel(req) }}
                                </div>
                                <div
                                    v-if="canManage && req.member_name"
                                    class="text-text-quaternary text-xs mt-0.5">
                                    {{ req.member_name }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Status -->
                    <td class="px-4 py-3">
                        <div class="flex flex-col gap-1.5">
                            <VacationRequestStatusBadge :status="req.status" />
                            <div
                                v-if="
                                    req.reviewer_name &&
                                    req.status !== 'pending' &&
                                    req.status !== 'withdrawn'
                                "
                                class="text-xs text-text-quaternary">
                                {{ req.reviewer_name }}
                            </div>
                        </div>
                    </td>

                    <!-- Notes -->
                    <td class="px-4 py-3">
                        <div v-if="req.private_note" class="text-text-tertiary text-xs">
                            <span class="font-medium text-text-secondary italic">Private note:</span>
                            <span class="italic ml-1">{{ req.private_note }}</span>
                        </div>
                        <div v-if="req.public_note" class="text-text-secondary text-xs mt-0.5">
                            {{ req.public_note }}
                        </div>
                    </td>

                    <!-- Actions -->
                    <td v-if="canManage" class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-1.5">
                            <template v-if="req.status === 'pending'">
                                <button
                                    class="px-2 py-1 rounded text-xs bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 transition"
                                    :disabled="updateStatus.isPending.value"
                                    @click="approve(req)">
                                    Approve
                                </button>
                                <button
                                    class="px-2 py-1 rounded text-xs bg-red-500/10 text-red-600 hover:bg-red-500/20 transition"
                                    :disabled="updateStatus.isPending.value"
                                    @click="reject(req)">
                                    Reject
                                </button>
                            </template>
                            <button
                                v-if="req.status !== 'withdrawn'"
                                class="px-2 py-1 rounded text-xs text-text-tertiary hover:bg-tertiary transition"
                                :disabled="updateStatus.isPending.value"
                                @click="withdraw(req)">
                                Withdraw
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
