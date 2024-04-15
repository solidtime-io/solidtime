<script setup lang="ts">
import type { Invitation } from '@/utils/api';
import TableRow from '@/Components/TableRow.vue';
import { capitalizeFirstLetter } from '../../../utils/format';

defineProps<{
    invitation: Invitation;
}>();
</script>

<template>
    <TableRow>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ invitation.email }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ capitalizeFirstLetter(invitation.role) }}
        </div>
    </TableRow>
</template>

<style scoped></style>
