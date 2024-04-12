<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-end sm:p-6">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <Notification
                v-for="notification in notifications"
                :type="notification.type"
                :key="notification.uuid"
                :title="notification.title"
                :message="notification.message"></Notification>
        </div>
    </div>
</template>

<script setup lang="ts">
import Notification from '@/Components/Common/Notification/Notification.vue';
import { storeToRefs } from 'pinia';
import { useNotificationsStore } from '@/utils/notification';

const { notifications } = storeToRefs(useNotificationsStore());
</script>
