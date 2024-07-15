<script setup lang="ts">
import DaySectionHeader from '@/Components/Common/DaySectionHeader.vue';
import MainContainer from '@/Pages/MainContainer.vue';
defineProps<{
    date: string;
}>();
</script>

<template>
    <div
        class="bg-card-background border-t border-b border-card-border py-1 lg:py-1.5 text-xs sm:text-sm">
        <MainContainer>
            <DaySectionHeader :date></DaySectionHeader>
        </MainContainer>
    </div>
</template>

<style scoped></style>
