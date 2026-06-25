<script setup lang="ts">
import { computed } from 'vue';
import type { VacationRequest } from '@/utils/useVacationRequests';

const props = defineProps<{
    requests: VacationRequest[];
    year: number;
}>();

const MONTHS = [
    'January', 'February', 'March', 'April',
    'May', 'June', 'July', 'August',
    'September', 'October', 'November', 'December',
];

const DAY_LABELS = ['Wk', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

function getWeekNumber(date: Date): number {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d.getTime() - yearStart.getTime()) / 86400000) + 1) / 7);
}

const vacationMap = computed(() => {
    const map = new Map<string, VacationRequest>();
    for (const req of props.requests) {
        if (req.status !== 'approved' && req.status !== 'pending') continue;
        const start = new Date(req.start_date);
        const end = new Date(req.end_date);
        const cur = new Date(start);
        while (cur <= end) {
            map.set(cur.toISOString().slice(0, 10), req);
            cur.setDate(cur.getDate() + 1);
        }
    }
    return map;
});

interface CalDay {
    date: Date | null;
    key: string;
    isWeekend: boolean;
    isToday: boolean;
    req?: VacationRequest;
}

interface CalWeek {
    weekNumber: number;
    days: CalDay[];
}

function buildMonth(year: number, monthIndex: number): CalWeek[] {
    const weeks: CalWeek[] = [];
    const firstDay = new Date(year, monthIndex, 1);
    const lastDay = new Date(year, monthIndex + 1, 0);
    const today = new Date().toISOString().slice(0, 10);

    let startDow = firstDay.getDay() - 1;
    if (startDow < 0) startDow = 6;

    const cur = new Date(firstDay);
    cur.setDate(cur.getDate() - startDow);

    while (cur <= lastDay || cur.getDay() !== 1) {
        const weekDays: CalDay[] = [];
        const wn = getWeekNumber(cur);

        for (let d = 0; d < 7; d++) {
            const isInMonth = cur.getMonth() === monthIndex;
            const key = cur.toISOString().slice(0, 10);
            const dow = cur.getDay();
            const isWeekend = dow === 0 || dow === 6;

            weekDays.push({
                date: isInMonth ? new Date(cur) : null,
                key,
                isWeekend,
                isToday: key === today,
                req: isInMonth ? vacationMap.value.get(key) : undefined,
            });
            cur.setDate(cur.getDate() + 1);
        }

        weeks.push({ weekNumber: wn, days: weekDays });

        if (cur > lastDay && (cur.getDay() === 1 || cur.getDay() === 0)) break;
    }

    return weeks;
}

const months = computed(() =>
    MONTHS.map((name, i) => ({ name, weeks: buildMonth(props.year, i) }))
);

function dayClass(day: CalDay) {
    if (!day.date) return 'text-transparent select-none';

    const base = 'w-6 h-6 flex items-center justify-center rounded text-xs font-medium mx-auto';

    if (day.req) {
        if (day.req.status === 'approved') {
            switch (day.req.type) {
                case 'regular_vacation': return `${base} bg-emerald-500 text-white`;
                case 'sick_day':         return `${base} bg-blue-500 text-white`;
                case 'work_outside':     return `${base} bg-yellow-400 text-white`;
                default:                 return `${base} bg-purple-500 text-white`;
            }
        }
        switch (day.req.type) {
            case 'regular_vacation': return `${base} border border-emerald-500 text-emerald-600`;
            case 'sick_day':         return `${base} border border-blue-500 text-blue-600`;
            case 'work_outside':     return `${base} border border-yellow-400 text-yellow-600`;
            default:                 return `${base} border border-purple-500 text-purple-600`;
        }
    }

    if (day.isToday)   return `${base} border border-default-background-separator font-bold text-text-primary`;
    if (day.isWeekend) return `${base} text-text-quaternary`;
    return `${base} text-text-secondary`;
}
</script>

<template>
    <div>
        <!-- Legend -->
        <div class="flex flex-wrap gap-x-6 gap-y-2 mb-6 text-xs text-text-secondary border-b border-default-background-separator pb-4">
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded border border-emerald-500 flex-shrink-0" />
                Requested vacation day
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded bg-emerald-500 flex-shrink-0" />
                Approved vacation day
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded border border-blue-500 flex-shrink-0" />
                Reported sick day
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded bg-blue-500 flex-shrink-0" />
                Approved sick day
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded border border-yellow-400 flex-shrink-0" />
                Requested work outside office
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded bg-yellow-400 flex-shrink-0" />
                Approved work outside office
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded bg-default-background border border-default-background-separator flex-shrink-0" />
                Work day
            </div>
            <div class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded flex-shrink-0" />
                <span class="text-text-quaternary">Non-working day</span>
            </div>
        </div>

        <!-- 4-column year grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-8">
            <div v-for="month in months" :key="month.name">
                <div class="text-sm font-semibold text-text-primary mb-2">{{ month.name }}</div>
                <table class="w-full border-collapse text-xs">
                    <thead>
                        <tr>
                            <th
                                v-for="label in DAY_LABELS"
                                :key="label"
                                class="text-center py-1 text-text-quaternary font-medium w-7">
                                {{ label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="week in month.weeks" :key="week.weekNumber">
                            <td class="text-center py-0.5 text-text-quaternary w-7 text-[10px]">
                                {{ week.weekNumber }}
                            </td>
                            <td
                                v-for="(day, di) in week.days"
                                :key="di"
                                class="py-0.5 w-7 text-center">
                                <div :class="dayClass(day)">
                                    {{ day.date ? day.date.getDate() : '' }}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
