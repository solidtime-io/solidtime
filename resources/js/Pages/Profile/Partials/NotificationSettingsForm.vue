<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { Checkbox } from '@/packages/ui/src';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/packages/ui/src/field';
import { getApiValidationFieldErrors } from '@/utils/apiValidation';
import { useUpdateUserMutation, useUserQuery } from '@/utils/useUserQuery';

const { user } = useUserQuery();
const updateUser = useUpdateUserMutation();

const sendTimeEntryStillRunningEmail = ref(true);
const recentlySaved = ref(false);

watch(
    user,
    (currentUser, previousUser) => {
        if (currentUser && previousUser === undefined) {
            sendTimeEntryStillRunningEmail.value = currentUser.send_time_entry_still_running_email;
        }
    },
    { immediate: true }
);

const isUserLoaded = computed(() => user.value !== undefined);
const isSaveDisabled = computed(() => !isUserLoaded.value || updateUser.isPending.value);
const fieldErrors = computed<Record<string, string>>(() =>
    getApiValidationFieldErrors(updateUser.error.value)
);

async function save() {
    if (isSaveDisabled.value || !user.value) return;

    if (sendTimeEntryStillRunningEmail.value === user.value.send_time_entry_still_running_email) {
        flashSaved();
        return;
    }

    try {
        const updatedUser = await updateUser.mutateAsync({
            userId: user.value.id,
            body: {
                send_time_entry_still_running_email: sendTimeEntryStillRunningEmail.value,
            },
        });
        sendTimeEntryStillRunningEmail.value = updatedUser.send_time_entry_still_running_email;
        flashSaved();
    } catch {
        // 422: field errors render below. Other errors: toast handled in the mutation.
    }
}

function flashSaved() {
    recentlySaved.value = true;
    setTimeout(() => (recentlySaved.value = false), 2000);
}
</script>

<template>
    <FormSection @submitted="save">
        <template #title>Notifications</template>

        <template #description>Choose which email notifications you want to receive.</template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <Field orientation="horizontal">
                    <Checkbox
                        id="send_time_entry_still_running_email"
                        v-model:checked="sendTimeEntryStillRunningEmail"
                        :disabled="!isUserLoaded" />
                    <FieldContent>
                        <FieldLabel for="send_time_entry_still_running_email">
                            Still-running time entry reminders
                        </FieldLabel>
                        <FieldDescription>
                            Email me when a time entry has been running for more than 8 hours.
                        </FieldDescription>
                    </FieldContent>
                </Field>
                <FieldError v-if="fieldErrors.send_time_entry_still_running_email">
                    {{ fieldErrors.send_time_entry_still_running_email }}
                </FieldError>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="recentlySaved" class="me-3">Saved.</ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': isSaveDisabled }" :disabled="isSaveDisabled">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
