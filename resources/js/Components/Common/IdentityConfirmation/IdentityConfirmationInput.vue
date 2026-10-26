<script setup lang="ts">
import { ref } from 'vue';
import { Field, FieldError } from '@/packages/ui/src/field';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';

const password = defineModel<string>('password', { required: true });

defineProps<{
    confirmsWithPassword: boolean;
    error: string;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const passwordInput = ref<InstanceType<typeof TextInput> | null>(null);

defineExpose({ focus: () => passwordInput.value?.focus() });
</script>

<template>
    <Field v-if="confirmsWithPassword" class="mt-4">
        <TextInput
            ref="passwordInput"
            v-model="password"
            type="password"
            class="block w-3/4"
            placeholder="Password"
            autocomplete="current-password"
            @keyup.enter="emit('submit')" />

        <FieldError v-if="error">{{ error }}</FieldError>
    </Field>

    <div v-else class="mt-4 text-sm">
        <p>Your account has no password to confirm this with. Contact your administrator.</p>

        <FieldError v-if="error" class="mt-2">{{ error }}</FieldError>
    </div>
</template>
