<script setup lang="ts">
import { Coffee } from '@lucide/vue';
</script>

<template>
    <div
        data-testid="break_badge"
        class="flex items-center space-x-1.5 text-sm font-medium text-text-secondary">
        <Coffee class="w-4 h-4 text-text-tertiary" />
        <span>Break</span>
    </div>
</template>

<style scoped></style>
