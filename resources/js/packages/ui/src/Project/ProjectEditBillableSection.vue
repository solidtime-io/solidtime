<script setup lang="ts">
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import ProjectBillableSelect from '@/packages/ui/src/Project/ProjectBillableSelect.vue';
import { computed, onMounted, ref, watch } from 'vue';
import type { BillableKey } from '@/types/projects';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';

defineProps<{
    currency: string;
}>();

const billableRateSelect = ref<BillableKey>('non-billable');

const billableRate = defineModel<number | null>('billableRate');
const isBillable = defineModel<boolean>('isBillable');

onMounted(() => {
    if (isBillable.value === true) {
        if (billableRate.value) {
            billableRateSelect.value = 'custom-rate';
        } else {
            billableRateSelect.value = 'default-rate';
        }
    }
});

watch(billableRateSelect, () => {
    if (billableRateSelect.value === 'non-billable') {
        isBillable.value = false;
        billableRate.value = null;
    } else if (billableRateSelect.value === 'default-rate') {
        isBillable.value = true;
        billableRate.value = null;
    } else {
        isBillable.value = true;
    }
});

billableRateSelect.value = 'non-billable';

const billableOptionInfoTexts: { [key in BillableKey]: string } = {
    'non-billable':
        'New time entries for this project will not be marked billable by default.',
    'default-rate':
        'New time entries for this project will be billable at the default rate by default.',
    'custom-rate':
        'New time entries for this project will be billable at a custom rate by default.',
};

const billableOptionInfoText = computed(() => {
    return billableOptionInfoTexts[billableRateSelect.value];
});
const emit = defineEmits(['submit']);
</script>

<template>
    <div class="sm:flex items-center space-y-2 sm:space-y-0 sm:space-x-4 pt-6">
        <div>
            <div class="flex items-center space-x-1 mb-2">
                <BillableIcon
                    class="text-text-quaternary h-4 ml-1 mr-0.5"></BillableIcon>
                <InputLabel for="billable" value="Billable Default" />
            </div>
            <ProjectBillableSelect
                v-model="billableRateSelect"
                class="mt-2"></ProjectBillableSelect>
        </div>
        <div
            v-if="billableRateSelect === 'custom-rate'"
            class="sm:max-w-[120px]">
            <InputLabel for="billableRate" value="Billable Rate" class="mb-2" />
            <BillableRateInput
                v-model="billableRate"
                :currency="currency"
                name="billableRate"
                @keydown.enter="emit('submit')" />
        </div>
    </div>
    <div class="flex items-center text-text-secondary text-xs pt-2 pl-1">
        <span>
            <span class="font-semibold"> Info: </span>
            {{ billableOptionInfoText }}
        </span>
    </div>
</template>

<style scoped></style>
