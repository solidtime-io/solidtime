<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';

defineEmits<{
    switchOrganization: [];
}>();
</script>

<template>
    <div class="absolute w-full h-full backdrop-blur-sm z-10 flex items-center justify-center">
        <div
            class="w-full h-[calc(100%+10px)] absolute bg-default-background opacity-75 backdrop-blur-sm"></div>
        <div class="flex space-x-3 items-center w-full z-20 justify-center">
            <span class="text-sm text-text-primary">
                The Timer is running in a different organization.
            </span>
            <SecondaryButton @click="$emit('switchOrganization')"
                >Switch to organization</SecondaryButton
            >
        </div>
    </div>
</template>

<style scoped></style>
