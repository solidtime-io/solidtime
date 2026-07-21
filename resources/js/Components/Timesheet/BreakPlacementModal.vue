<script setup lang="ts">
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TimeRangeFields from '@/packages/ui/src/TimeEntry/TimeRangeFields.vue';
import { formatTime, getDayJsInstance, getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import { Coffee } from '@lucide/vue';
import { computed, inject, ref, watch, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';
import {
    BREAK_GAP_TOLERANCE_SECONDS,
    placementMode,
    planMoveInsert,
    planSplitEntry,
    type BreakPlacementRequest,
} from '@/utils/timesheet/breakPlacementMath';
import { BREAK_GAP_TOLERANCE_MINUTES } from '@/packages/ui/src/utils/breakPlacement';

const props = defineProps<{
    request: BreakPlacementRequest | null;
    apply: (breakStart: string, durationSeconds: number) => Promise<void>;
}>();

const emit = defineEmits<{ cancel: [] }>();

const organization = inject<ComputedRef<Organization>>('organization');

const show = computed(() => props.request !== null);
const mode = computed(() => (props.request ? placementMode(props.request) : null));
const saving = ref(false);

const localStart = ref('');
const localEnd = ref('');

// Seed the pickers from the suggested placement whenever a new request arrives.
watch(
    () => props.request,
    (request) => {
        if (!request) return;
        localStart.value = getLocalizedDayJs(request.defaultBreakStart).format();
        localEnd.value = getLocalizedDayJs(request.defaultBreakStart)
            .add(request.durationSeconds, 'second')
            .format();
    },
    { immediate: true }
);

const utcStart = computed(() => getLocalizedDayJs(localStart.value).utc().format());
const durationSeconds = computed(() =>
    getLocalizedDayJs(localEnd.value)
        .utc()
        .diff(getLocalizedDayJs(localStart.value).utc(), 'second')
);

const splitPlan = computed(() => {
    if (!props.request || mode.value !== 'split' || durationSeconds.value <= 0) return null;
    return planSplitEntry(props.request.workEntries[0]!, durationSeconds.value, utcStart.value);
});

const movePlan = computed(() => {
    if (!props.request || mode.value !== 'move' || durationSeconds.value <= 0) return null;
    return planMoveInsert(
        [...props.request.workEntries, ...props.request.otherEntries],
        props.request.dayStart,
        props.request.dayEnd,
        utcStart.value,
        durationSeconds.value
    );
});

// Non-blocking heads-up: the placement is feasible but the break would end up
// further than the tolerance from work on either side, so it would carry the
// misaligned warning right after being created. Mirrors getBreakPlacementHint,
// but computed against the planned (post-shift) layout.
const resultMisaligned = computed<boolean>(() => {
    const req = props.request;
    const plan = movePlan.value;
    if (!req || mode.value !== 'move' || !plan) return false;
    const dayjs = getDayJsInstance();
    const toMs = (iso: string) => dayjs.utc(iso).valueOf();
    const breakStartMs = toMs(plan.breakSlot.start);
    const breakEndMs = toMs(plan.breakSlot.end);
    const shiftedById = new Map(plan.shifted.map((s) => [s.id, s]));

    let prevWorkEndMs: number | null = null;
    let nextWorkStartMs: number | null = null;
    for (const entry of req.workEntries) {
        const planned = shiftedById.get(entry.id) ?? entry;
        const startMs = toMs(planned.start);
        const endMs = toMs(planned.end);
        if (endMs <= breakStartMs && (prevWorkEndMs === null || endMs > prevWorkEndMs)) {
            prevWorkEndMs = endMs;
        }
        if (startMs >= breakEndMs && (nextWorkStartMs === null || startMs < nextWorkStartMs)) {
            nextWorkStartMs = startMs;
        }
    }

    const toleranceMs = BREAK_GAP_TOLERANCE_SECONDS * 1000;
    return (
        prevWorkEndMs === null ||
        breakStartMs - prevWorkEndMs > toleranceMs ||
        nextWorkStartMs === null ||
        nextWorkStartMs - breakEndMs > toleranceMs
    );
});

const feasible = computed(() =>
    mode.value === 'split' ? splitPlan.value !== null : movePlan.value !== null
);

function fmt(iso: string): string {
    return formatTime(iso, organization?.value?.time_format);
}

const explanation = computed(() => {
    if (!props.request) return '';
    return mode.value === 'split'
        ? "There's no free gap that fits this break, so the work entry will be split and the break placed inside it."
        : "There's no free gap that fits this break, so the surrounding entries will be shifted to make room.";
});

// Human-readable summary of what will change, so the user can confirm the edit.
const changeSummary = computed<string[]>(() => {
    if (mode.value === 'split') {
        const plan = splitPlan.value;
        if (!plan) return [];
        return [
            `${fmt(plan.firstHalf.start)}–${fmt(plan.firstHalf.end)} (work)`,
            `${fmt(plan.breakSlot.start)}–${fmt(plan.breakSlot.end)} (break)`,
            `${fmt(plan.secondHalf.start)}–${fmt(plan.secondHalf.end)} (work)`,
        ];
    }
    const plan = movePlan.value;
    if (!plan) return [];
    if (plan.shifted.length === 0) return ['No entries need to move.'];
    return plan.shifted.map((shift) => {
        const isBreak = props.request!.otherEntries.some((e) => e.id === shift.id);
        const original =
            props.request!.workEntries.find((e) => e.id === shift.id) ??
            props.request!.otherEntries.find((e) => e.id === shift.id)!;
        const label = `${fmt(original.start)}–${fmt(original.end)} → ${fmt(shift.start)}–${fmt(shift.end)}`;
        return isBreak ? `${label} (break)` : label;
    });
});

async function submit() {
    if (!feasible.value || durationSeconds.value <= 0) return;
    saving.value = true;
    try {
        await props.apply(utcStart.value, durationSeconds.value);
    } catch {
        // apply surfaces its own error toast; keep the modal open so the user can retry
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <DialogModal closeable :show="show" @close="emit('cancel')">
        <template #title>
            <div class="flex items-center space-x-2">
                <Coffee class="w-5 h-5 text-text-secondary" />
                <span>Add break</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-4">
                <p class="text-sm text-text-secondary">{{ explanation }}</p>

                <TimeRangeFields
                    v-model:start="localStart"
                    v-model:end="localEnd"
                    date-picker-size="sm"></TimeRangeFields>

                <div
                    v-if="feasible"
                    data-testid="break_placement_summary"
                    class="rounded-lg border border-card-border bg-secondary/40 px-3 py-2 text-sm text-text-secondary space-y-1">
                    <div class="text-xs uppercase tracking-wide text-text-tertiary">
                        {{ mode === 'split' ? 'Result' : 'Entries that move' }}
                    </div>
                    <div v-for="(line, index) in changeSummary" :key="index" class="tabular-nums">
                        {{ line }}
                    </div>
                </div>
                <div
                    v-if="feasible && resultMisaligned"
                    data-testid="break_placement_misaligned_warning"
                    class="rounded-lg border border-yellow-500/30 bg-yellow-500/10 px-3 py-2 text-sm text-yellow-700 dark:text-yellow-400">
                    At this time the break would sit more than
                    {{ BREAK_GAP_TOLERANCE_MINUTES }} minutes away from your work entries and will
                    be flagged as misaligned.
                </div>
                <!-- `request` guard (not just !feasible): when the request is cleared on save,
                     the dialog fades out with content still mounted — don't flash the error then -->
                <div
                    v-if="!feasible && request"
                    data-testid="break_placement_infeasible"
                    class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-600 dark:text-red-400">
                    {{
                        mode === 'split'
                            ? "This break doesn't fit there — it must lie inside the work entry, leaving at least a minute of work on each side."
                            : "This break doesn't fit at that time without pushing an entry outside the day. Try a shorter break or a different time."
                    }}
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="emit('cancel')">Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving || !feasible }"
                :disabled="saving || !feasible"
                @click="submit">
                Add break
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
