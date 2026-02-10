<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import type { UpdateOrganizationBody } from '@/packages/api/src';
import { useOrganizationStore } from '@/utils/useOrganization';
import { storeToRefs } from 'pinia';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import type { DateFormat, TimeFormat, IntervalFormat } from '@/packages/ui/src/utils/time';
import type { CurrencyFormat } from '@/packages/ui/src/utils/money';
import type { NumberFormat } from '@/packages/ui/src/utils/number';

interface FormValues {
    number_format: NumberFormat | undefined;
    currency_format: CurrencyFormat | undefined;
    date_format: DateFormat | undefined;
    time_format: TimeFormat | undefined;
    interval_format: IntervalFormat | undefined;
}

const store = useOrganizationStore();
const { updateOrganization } = store;
const { organization } = storeToRefs(store);
const queryClient = useQueryClient();

const form = ref<FormValues>({
    number_format: undefined,
    currency_format: undefined,
    date_format: undefined,
    time_format: undefined,
    interval_format: undefined,
});

const mutation = useMutation({
    mutationFn: (values: FormValues) => updateOrganization(values as UpdateOrganizationBody),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['organization'] });
    },
});

onMounted(async () => {
    if (organization.value) {
        form.value = {
            number_format: organization.value.number_format as NumberFormat,
            currency_format: organization.value.currency_format as CurrencyFormat,
            date_format: organization.value.date_format as DateFormat,
            time_format: organization.value.time_format as TimeFormat,
            interval_format: organization?.value.interval_format as IntervalFormat,
        };
    }
});

async function submit() {
    mutation.mutate(form.value);
}
</script>

<template>
    <FormSection>
        <template #title>Format Settings</template>

        <template #description>
            Configure the default format settings for the organization.
        </template>

        <template #form>
            <!-- Number Format -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="numberFormat">Number Format</FieldLabel>
                <Select v-model="form.number_format">
                    <SelectTrigger id="numberFormat">
                        <SelectValue placeholder="Select number format" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="point-comma">1.111,11</SelectItem>
                        <SelectItem value="comma-point">1,111.11</SelectItem>
                        <SelectItem value="space-comma">1 111,11</SelectItem>
                        <SelectItem value="space-point">1 111.11</SelectItem>
                        <SelectItem value="apostrophe-point">1'111.11</SelectItem>
                    </SelectContent>
                </Select>
            </Field>

            <!-- Currency Format -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="currencyFormat">Currency Format</FieldLabel>
                <Select v-model="form.currency_format">
                    <SelectTrigger id="currencyFormat">
                        <SelectValue placeholder="Select currency format" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="iso-code-before-with-space">EUR 111</SelectItem>
                        <SelectItem value="iso-code-after-with-space">111 EUR</SelectItem>
                        <SelectItem value="symbol-before">€111</SelectItem>
                        <SelectItem value="symbol-after">111€</SelectItem>
                        <SelectItem value="symbol-before-with-space">€ 111</SelectItem>
                        <SelectItem value="symbol-after-with-space">111 €</SelectItem>
                    </SelectContent>
                </Select>
            </Field>

            <!-- Date Format -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="dateFormat">Date Format</FieldLabel>
                <Select v-model="form.date_format">
                    <SelectTrigger id="dateFormat">
                        <SelectValue placeholder="Select date format" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="point-separated-d-m-yyyy">D.M.YYYY</SelectItem>
                        <SelectItem value="slash-separated-mm-dd-yyyy">MM/DD/YYYY</SelectItem>
                        <SelectItem value="slash-separated-dd-mm-yyyy">DD/MM/YYYY</SelectItem>
                        <SelectItem value="hyphen-separated-dd-mm-yyyy">DD-MM-YYYY</SelectItem>
                        <SelectItem value="hyphen-separated-mm-dd-yyyy">MM-DD-YYYY</SelectItem>
                        <SelectItem value="hyphen-separated-yyyy-mm-dd">YYYY-MM-DD</SelectItem>
                    </SelectContent>
                </Select>
            </Field>

            <!-- Time Format -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="timeFormat">Time Format</FieldLabel>
                <Select v-model="form.time_format">
                    <SelectTrigger id="timeFormat">
                        <SelectValue placeholder="Select time format" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="12-hours">12-hour clock</SelectItem>
                        <SelectItem value="24-hours">24-hour clock</SelectItem>
                    </SelectContent>
                </Select>
            </Field>

            <!-- Interval Format -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="intervalFormat">Time Duration Format</FieldLabel>
                <Select v-model="form.interval_format">
                    <SelectTrigger id="intervalFormat">
                        <SelectValue placeholder="Select interval format" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="decimal">Decimal</SelectItem>
                        <SelectItem value="hours-minutes">12h 3m</SelectItem>
                        <SelectItem value="hours-minutes-colon-separated">12:03</SelectItem>
                        <SelectItem value="hours-minutes-seconds-colon-separated"
                            >12:03:45</SelectItem
                        >
                    </SelectContent>
                </Select>
            </Field>
        </template>

        <template #actions>
            <PrimaryButton :disabled="mutation.isPending.value" @click="submit">
                {{ mutation.isPending.value ? 'Saving...' : 'Save' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
