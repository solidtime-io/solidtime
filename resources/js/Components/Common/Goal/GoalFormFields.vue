<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import EstimatedTimeInput from '@/packages/ui/src/Input/EstimatedTimeInput.vue';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/packages/ui/src/field';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/packages/ui/src';
import { QuestionMarkCircleIcon } from '@heroicons/vue/20/solid';
import GoalTimeEntryFilters from '@/Components/Common/Goal/GoalTimeEntryFilters.vue';
import TimezoneCombobox from '@/Components/Common/TimezoneCombobox.vue';
import WeekStartSelect from '@/Components/Common/WeekStartSelect.vue';
import {
    goalComparisonOptions,
    goalPeriodOptions,
    goalTargetOptions,
    type GoalTarget,
} from '@/utils/goals';
import { isGoalsExtensionActivated } from '@/utils/billing';
import { canCreateOrganizationGoals, canViewMembers } from '@/utils/permissions';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { getCurrentMembershipId } from '@/utils/useUser';
import type { GoalFormData, GoalFormErrors } from '@/Components/Common/Goal/goalForm';
import { useFocus } from '@vueuse/core';

const model = defineModel<GoalFormData>({ required: true });

const props = defineProps<{
    errors: GoalFormErrors;
    // Who a goal is for is decided once, it can not be changed after creation
    disableTarget?: boolean;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const nameInput = ref<HTMLInputElement | null>(null);
useFocus(nameInput, { initialValue: true });

const goalsExtensionActivated = isGoalsExtensionActivated();
// Goals for another member and goals that count every member are organization goals of the team goals extension
const canChooseOrganizationTarget = goalsExtensionActivated && canCreateOrganizationGoals();
const { members } = useMembersQuery({
    enabled: () => goalsExtensionActivated && canViewMembers(),
});
const currentMembershipId = getCurrentMembershipId() ?? null;
const otherMembers = computed(() =>
    members.value.filter(
        (member) => member.id !== currentMembershipId && member.is_placeholder === false
    )
);
// Without the permission to create organization goals there is nothing to choose
const targetOptions = computed(() =>
    canChooseOrganizationTarget
        ? goalTargetOptions
        : goalTargetOptions.filter((option) => option.value === 'me')
);
const targetDescriptions: Record<GoalTarget, string> = {
    me: 'Only you can see this goal.',
    other: 'The member and everyone who manages goals can see it.',
    organization: 'Only people who manage goals can see it.',
};
// Only a goal that counts every member can be narrowed down to a subset of the members, placeholders can not be selected
const memberFilterOptions = computed(() =>
    model.value.target === 'organization'
        ? members.value
              .filter((member) => member.is_placeholder === false)
              .map((member) => ({ id: member.id, name: member.name }))
        : null
);
// A goal for one member has no member filter, and a goal that counts every member has no single member
watch(
    () => model.value.target,
    (target) => {
        if (target !== 'other') {
            model.value.other_member_id = null;
        }
        if (target !== 'organization') {
            model.value.member_ids = [];
        }
    }
);

const errors = computed(() => props.errors);

// The settings are collapsed by default, open them when one of their fields has an error
const openSettings = ref<string | undefined>(undefined);
watch(errors, (newErrors) => {
    if (newErrors.timezone || newErrors.week_start) {
        openSettings.value = 'period-settings';
    }
});
</script>

<template>
    <FieldGroup>
        <Field>
            <FieldLabel for="goalName">Goal name</FieldLabel>
            <TextInput
                id="goalName"
                ref="nameInput"
                v-model="model.name"
                type="text"
                placeholder="e.g. Deep work on Project X"
                class="block w-full"
                required
                @keydown.enter="emit('submit')" />
            <FieldError v-if="errors.name">{{ errors.name }}</FieldError>
        </Field>
        <Field v-if="canChooseOrganizationTarget">
            <FieldLabel for="goalFor">This goal is for</FieldLabel>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
                <Select v-model="model.target" :disabled="props.disableTarget">
                    <SelectTrigger
                        id="goalFor"
                        class="w-full sm:w-1/2"
                        aria-label="This goal is for">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in targetOptions"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <div v-if="model.target === 'other'" class="flex flex-col gap-1.5 sm:w-1/2">
                    <Select v-model="model.other_member_id" :disabled="props.disableTarget">
                        <SelectTrigger id="goalMember" class="w-full" aria-label="Member">
                            <SelectValue placeholder="Choose a member" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="member in otherMembers"
                                :key="member.id"
                                :value="member.id"
                                >{{ member.name }}</SelectItem
                            >
                        </SelectContent>
                    </Select>
                    <FieldError v-if="errors.other_member_id">{{
                        errors.other_member_id
                    }}</FieldError>
                </div>
            </div>
            <FieldDescription>{{ targetDescriptions[model.target] }}</FieldDescription>
        </Field>
        <FieldGroup class="gap-4 sm:flex-row sm:items-start">
            <Field class="sm:w-1/3">
                <FieldLabel for="goalComparison">Target type</FieldLabel>
                <Select v-model="model.comparison">
                    <SelectTrigger id="goalComparison" class="w-full" aria-label="Target type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in goalComparisonOptions"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <FieldError v-if="errors.comparison">{{ errors.comparison }}</FieldError>
            </Field>
            <Field class="sm:w-1/3">
                <div class="flex items-center gap-1">
                    <FieldLabel for="goalTarget">Target time</FieldLabel>
                    <TooltipProvider :delay-duration="100">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <button
                                    type="button"
                                    class="text-icon-default hover:text-text-primary cursor-default rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    aria-label="How to enter the target time">
                                    <QuestionMarkCircleIcon class="w-4 h-4" />
                                </button>
                            </TooltipTrigger>
                            <TooltipContent class="max-w-64">
                                You can type natural language for the target time like
                                <span class="font-semibold">2h 30m</span> or
                                <span class="font-semibold">1.5</span> hours.
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
                <EstimatedTimeInput
                    id="goalTarget"
                    v-model="model.target_seconds"
                    @submit="emit('submit')"></EstimatedTimeInput>
                <FieldError v-if="errors.target_seconds">{{ errors.target_seconds }}</FieldError>
            </Field>
            <Field class="sm:w-1/3">
                <FieldLabel for="goalPeriod">Period</FieldLabel>
                <Select v-model="model.period">
                    <SelectTrigger id="goalPeriod" class="w-full" aria-label="Period">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in goalPeriodOptions"
                            :key="option.value"
                            :value="option.value"
                            >{{ option.label }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <FieldError v-if="errors.period">{{ errors.period }}</FieldError>
            </Field>
        </FieldGroup>
        <Field>
            <FieldLabel>Only count time entries with</FieldLabel>
            <GoalTimeEntryFilters v-model="model" :member-options="memberFilterOptions" />
            <FieldError v-if="errors.filters">{{ errors.filters }}</FieldError>
            <FieldDescription>
                Values within a filter use "or", filters use "and". Without filters every entry
                counts.
            </FieldDescription>
        </Field>
        <Accordion v-model="openSettings" type="single" collapsible>
            <AccordionItem value="period-settings">
                <AccordionTrigger data-testid="goal_period_settings">
                    Additional Settings
                </AccordionTrigger>
                <AccordionContent>
                    <FieldGroup class="sm:flex-row sm:items-start pt-2">
                        <Field class="sm:w-1/2">
                            <FieldLabel for="goalTimezone">Timezone</FieldLabel>
                            <TimezoneCombobox id="goalTimezone" v-model="model.timezone" />
                            <FieldError v-if="errors.timezone">{{ errors.timezone }}</FieldError>
                        </Field>
                        <Field class="sm:w-1/2">
                            <FieldLabel for="goalWeekStart">Start of the week</FieldLabel>
                            <WeekStartSelect id="goalWeekStart" v-model="model.week_start" />
                            <FieldError v-if="errors.week_start">{{
                                errors.week_start
                            }}</FieldError>
                        </Field>
                    </FieldGroup>
                    <FieldDescription class="pt-2">
                        Timezone and start of the week decide when a period of this goal begins and
                        ends. They default to your own settings.
                    </FieldDescription>
                </AccordionContent>
            </AccordionItem>
        </Accordion>
    </FieldGroup>
</template>

<style scoped></style>
