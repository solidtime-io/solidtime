<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, onMounted, ref, watch } from 'vue';
import type { Member, UpdateMemberBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { type MemberBillableKey, useMembersStore } from '@/utils/useMembers';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import { Field, FieldLabel, FieldDescription } from '@/packages/ui/src/field';
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
import MemberBillableRateModal from '@/Components/Common/Member/MemberBillableRateModal.vue';
import MemberRoleSelect from '@/Components/Common/Member/MemberRoleSelect.vue';
import MemberOwnershipTransferConfirmModal from '@/Components/Common/Member/MemberOwnershipTransferConfirmModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';

const { updateMember } = useMembersStore();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    member: Member;
}>();

const memberBody = ref<UpdateMemberBody>({
    // @ts-expect-error - The role value is always valid
    role: props.member.role,
    billable_rate: props.member.billable_rate,
});

async function submitBillableRate() {
    if (memberBody.value.role === 'owner' && props.member.role !== 'owner') {
        show.value = false;
        showOwnershipTransferConfirmModal.value = true;
    } else {
        await submit();
    }
}

async function submit() {
    await updateMember(props.member.id, memberBody.value);
    show.value = false;
    showBillableRateModal.value = false;
    showOwnershipTransferConfirmModal.value = false;
}

const showBillableRateModal = ref(false);
const showOwnershipTransferConfirmModal = ref(false);

function saveWithChecks() {
    if (memberBody.value.billable_rate !== props.member.billable_rate) {
        // make sure that the alert modal is not immediately submitted when user presses enter
        setTimeout(() => {
            showBillableRateModal.value = true;
        }, 0);
        show.value = false;
    } else if (memberBody.value.role === 'owner' && props.member.role !== 'owner') {
        show.value = false;
        showOwnershipTransferConfirmModal.value = true;
    } else {
        submitBillableRate();
    }
}

const billableRateSelect = ref<MemberBillableKey>('default-rate');

onMounted(() => {
    if (props.member.billable_rate !== null) {
        billableRateSelect.value = 'custom-rate';
    } else {
        billableRateSelect.value = 'default-rate';
    }
});
watch(billableRateSelect, () => {
    if (billableRateSelect.value === 'default-rate') {
        memberBody.value.billable_rate = null;
    } else if (billableRateSelect.value === 'custom-rate') {
        if (!memberBody.value.billable_rate) {
            memberBody.value.billable_rate = organization.value?.billable_rate ?? 0;
        }
    }
});

const displayedRate = computed({
    get() {
        if (billableRateSelect.value === 'default-rate') {
            return organization.value?.billable_rate ?? null;
        }
        return memberBody.value.billable_rate;
    },
    set(value: number | null) {
        if (billableRateSelect.value === 'custom-rate') {
            memberBody.value.billable_rate = value;
        }
    },
});

const roleDescriptionTexts = {
    'owner':
        'The owner has full access of the organization. The owner is the only role that can: delete the organization, transfer the ownership to another user and access to the billing settings',
    'admin':
        'The admin has full access to the organization, except for the stuff that only the owner can do.',
    'manager':
        'The manager has full access to projects, clients, tags, time entries, and reports, but can not manage the organization or the users.',
    'employee':
        'An employee is a user that is only using the application to track time, but has no administrative rights.',
    'placeholder':
        'Placeholder users can not do anything in the organization. They are not billed and can be used to remove users from the organization without deleting their time entries.',
};

const roleDescription = computed(() => {
    if (memberBody.value.role && memberBody.value.role in roleDescriptionTexts) {
        return roleDescriptionTexts[memberBody.value.role];
    }
    return '';
});
</script>

<template>
    <MemberBillableRateModal
        v-model:saving="saving"
        v-model:show="showBillableRateModal"
        :member-name="member.name"
        :new-billable-rate="memberBody.billable_rate"
        @submit="submitBillableRate"></MemberBillableRateModal>
    <MemberOwnershipTransferConfirmModal
        v-model:show="showOwnershipTransferConfirmModal"
        :member-name="member.name"
        @submit="submit"></MemberOwnershipTransferConfirmModal>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Update Member </span>
            </div>
        </template>

        <template #content>
            <div class="pb-5 pt-2 divide-y divide-border-secondary">
                <div class="pb-5">
                    <Field>
                        <FieldLabel for="role">Role</FieldLabel>
                        <MemberRoleSelect v-model="memberBody.role" name="role"></MemberRoleSelect>
                        <FieldDescription v-if="roleDescription">{{
                            roleDescription
                        }}</FieldDescription>
                    </Field>
                </div>
                <div class="pt-5">
                    <Field>
                        <FieldLabel :icon="BillableIcon" for="billableRateType"
                            >Billable Rate</FieldLabel
                        >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <Select v-model="billableRateSelect">
                                <SelectTrigger id="billableRateType">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="default-rate">Default Rate</SelectItem>
                                    <SelectItem value="custom-rate">Custom Rate</SelectItem>
                                </SelectContent>
                            </Select>
                            <TooltipProvider v-if="billableRateSelect === 'default-rate'">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <div>
                                            <BillableRateInput
                                                v-model="displayedRate"
                                                :currency="getOrganizationCurrencyString()"
                                                disabled
                                                name="memberBillableRate" />
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent
                                        >Uses the default rate of the organization</TooltipContent
                                    >
                                </Tooltip>
                            </TooltipProvider>
                            <BillableRateInput
                                v-else
                                v-model="displayedRate"
                                focus
                                :currency="getOrganizationCurrencyString()"
                                name="memberBillableRate"
                                @keydown.enter="saveWithChecks()" />
                        </div>
                    </Field>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="saveWithChecks()">
                Update Member
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
