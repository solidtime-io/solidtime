<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';
import TimezoneMismatchModal from '@/packages/ui/src/TimezoneMismatchModal.vue';
import { useUpdateUserMutation } from '@/utils/useUserQuery';

const show = defineModel('show', { default: false });

const page = usePage<{
    auth: {
        user: User;
    };
}>();

const updateUser = useUpdateUserMutation();

async function handleUpdate(timezone: string) {
    try {
        await updateUser.mutateAsync({
            userId: page.props.auth.user.id,
            body: { timezone },
        });
        show.value = false;
        // reload the whole page to re-read the timezone update
        location.reload();
    } catch {
        // notification handled by mutation
    }
}
</script>

<template>
    <TimezoneMismatchModal
        v-model:show="show"
        :saving="updateUser.isPending.value"
        @update="handleUpdate" />
</template>

<style scoped></style>
