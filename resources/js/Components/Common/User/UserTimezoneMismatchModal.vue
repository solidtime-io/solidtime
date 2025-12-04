<script setup lang="ts">
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';
import TimezoneMismatchModal from '@/packages/ui/src/TimezoneMismatchModal.vue';

const show = defineModel('show', { default: false });
const saving = ref(false);

const page = usePage<{
    auth: {
        user: User;
    };
}>();

function handleUpdate(timezone: string) {
    saving.value = true;
    const form = useForm({
        _method: 'PUT',
        timezone: timezone,
        name: page.props.auth.user.name,
        email: page.props.auth.user.email,
        week_start: page.props.auth.user.week_start,
    });

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false;
            show.value = false;
            location.reload();
        },
        onError: () => {
            saving.value = false;
        },
    });
}
</script>

<template>
    <TimezoneMismatchModal v-model:show="show" :saving="saving" @update="handleUpdate" />
</template>

<style scoped></style>
