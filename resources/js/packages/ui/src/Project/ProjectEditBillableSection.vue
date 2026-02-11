<script setup lang="ts">
import { Field, FieldDescription, FieldLabel } from '../field';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/packages/ui/src/tooltip';
import { computed, onMounted, ref, watch } from 'vue';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';

defineProps<{
    currency: string;
}>();

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

type RateType = 'default-rate' | 'custom-rate';

const billableDefault = ref<'billable' | 'non-billable'>('non-billable');
const rateType = ref<RateType>('default-rate');

const billableRate = defineModel<number | null>('billableRate');
const isBillable = defineModel<boolean>('isBillable');

onMounted(() => {
    if (isBillable.value === true) {
        billableDefault.value = 'billable';
        rateType.value = billableRate.value ? 'custom-rate' : 'default-rate';
    }
});

watch(billableDefault, () => {
    if (billableDefault.value === 'non-billable') {
        isBillable.value = false;
    } else {
        isBillable.value = true;
    }
});

watch(rateType, () => {
    if (rateType.value === 'default-rate') {
        billableRate.value = null;
    } else if (rateType.value === 'custom-rate') {
        billableDefault.value = 'billable';
        isBillable.value = true;
        if (!billableRate.value) {
            billableRate.value = organization.value?.billable_rate ?? null;
        }
    }
});

const displayedRate = computed({
    get() {
        if (rateType.value === 'default-rate') {
            return organization.value?.billable_rate ?? null;
        }
        return billableRate.value;
    },
    set(value: number | null) {
        if (rateType.value === 'custom-rate') {
            billableRate.value = value;
        }
    },
});

const billableDescription = computed(() => {
    if (billableDefault.value === 'non-billable') {
        return 'New time entries for this project will not be marked billable by default.';
    }
    return 'New time entries for this project will be marked billable by default.';
});

const emit = defineEmits(['submit']);
</script>

<template>
    <Field>
        <FieldLabel for="billable" :icon="BillableIcon">Billable Default</FieldLabel>
        <Select v-model="billableDefault">
            <SelectTrigger id="billable">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="non-billable">Non-billable</SelectItem>
                <SelectItem value="billable">Billable</SelectItem>
            </SelectContent>
        </Select>
        <FieldDescription>{{ billableDescription }}</FieldDescription>
    </Field>
    <Field>
        <FieldLabel :icon="BillableIcon" for="billableRateType">Billable Rate</FieldLabel>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <Select v-model="rateType">
                <SelectTrigger id="billableRateType">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="default-rate">Default Rate</SelectItem>
                    <SelectItem value="custom-rate">Custom Rate</SelectItem>
                </SelectContent>
            </Select>
            <TooltipProvider v-if="rateType === 'default-rate'">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div>
                            <BillableRateInput
                                v-model="displayedRate"
                                :currency="currency"
                                disabled
                                name="billableRate" />
                        </div>
                    </TooltipTrigger>
                    <TooltipContent> Uses the default rate of the organization </TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <BillableRateInput
                v-else
                v-model="displayedRate"
                :currency="currency"
                name="billableRate"
                @keydown.enter="emit('submit')" />
        </div>
    </Field>
</template>

<style scoped></style>
