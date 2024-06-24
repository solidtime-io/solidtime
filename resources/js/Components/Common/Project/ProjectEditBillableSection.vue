<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
import ProjectBillableSelect from '@/Components/Common/Project/ProjectBillableSelect.vue';
import { computed, onMounted, ref, watch } from 'vue';
import type { BillableKey } from '@/utils/useProjects';

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
        'New time entries for this project not be marked billable by default.',
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
            <InputLabel for="billable" value="Billable Default" />
            <ProjectBillableSelect
                v-model="billableRateSelect"
                class="mt-2"></ProjectBillableSelect>
        </div>
        <div
            class="sm:max-w-[120px]"
            v-if="billableRateSelect === 'custom-rate'">
            <InputLabel for="billableRate" value="Billable Rate" />
            <BillableRateInput
                @keydown.enter="emit('submit')"
                v-model="billableRate"
                name="billableRate" />
        </div>
    </div>
    <div class="flex items-center text-muted pt-2 pl-1">
        <span>
            <span class="font-semibold"> Info: </span>
            {{ billableOptionInfoText }}
        </span>
    </div>
</template>

<style scoped></style>
