<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/packages/ui/src/Input/InputError.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';

const recovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref<HTMLInputElement | null>(null);
const codeInput = ref<HTMLInputElement | null>(null);

const toggleRecovery = async () => {
    recovery.value = !recovery.value;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value?.focus();
        form.code = '';
    } else {
        codeInput.value?.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Two-factor Confirmation" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-4 text-sm text-text-secondary">
            <template v-if="!recovery">
                Please confirm access to your account by entering the
                authentication code provided by your authenticator application.
            </template>

            <template v-else>
                Please confirm access to your account by entering one of your
                emergency recovery codes.
            </template>
        </div>

        <form @submit.prevent="submit">
            <div v-if="!recovery">
                <InputLabel for="code" value="Code" />
                <TextInput
                    id="code"
                    ref="codeInput"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    class="mt-1 block w-full"
                    autofocus
                    autocomplete="one-time-code" />
                <InputError class="mt-2" :message="form.errors.code" />
            </div>

            <div v-else>
                <InputLabel for="recovery_code" value="Recovery Code" />
                <TextInput
                    id="recovery_code"
                    ref="recoveryCodeInput"
                    v-model="form.recovery_code"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="one-time-code" />
                <InputError class="mt-2" :message="form.errors.recovery_code" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <button
                    type="button"
                    class="text-sm text-text-secondary hover:text-text-primary underline cursor-pointer"
                    @click.prevent="toggleRecovery">
                    <template v-if="!recovery"> Use a recovery code</template>

                    <template v-else> Use an authentication code</template>
                </button>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
