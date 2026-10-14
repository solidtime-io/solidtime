<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import UpgradeModal from '@/packages/ui/src/UpgradeModal.vue';
import GoalCreateModal from '@/Components/Common/Goal/GoalCreateModal.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';

const props = defineProps<{
    canCreateMoreGoals: boolean;
}>();

const showCreateGoalModal = ref(false);
const showUpgradeModal = ref(false);

// The button stays visible when the goal limit is reached and explains the limit instead
function onClick() {
    if (props.canCreateMoreGoals) {
        showCreateGoalModal.value = true;
    } else {
        showUpgradeModal.value = true;
    }
}
</script>

<template>
    <GoalCreateModal v-model:show="showCreateGoalModal"></GoalCreateModal>
    <UpgradeModal v-model:show="showUpgradeModal" feature="goals" />
    <SecondaryButton :icon="PlusIcon" @click="onClick">
        <slot>Create Goal</slot>
    </SecondaryButton>
</template>
