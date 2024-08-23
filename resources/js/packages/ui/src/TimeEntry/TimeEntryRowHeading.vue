<script setup lang="ts">
import DaySectionHeader from '@/packages/ui/src/DaySectionHeader.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
defineProps<{
    date: string;
    duration: number;
}>();
</script>

<template>
    <div
        class="bg-card-background border-t border-b border-card-border py-1 lg:py-1.5 text-xs sm:text-sm">
        <MainContainer>
            <DaySectionHeader :date :duration></DaySectionHeader>
        </MainContainer>
    </div>
</template>

<style scoped></style>
