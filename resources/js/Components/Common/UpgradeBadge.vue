<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/20/solid';
</script>

<template>
    <div
        class="inline-flex bg-secondary hover:bg-tertiary px-2 py-1 rounded border border-border-secondary hover:border-border-tertiary items-center space-x-1">
        <LockClosedIcon class="w-3 text-text-tertiary"></LockClosedIcon>
        <span class="text-xs text-text-secondary font-semibold"> Upgrade </span>
    </div>
</template>

<style scoped></style>
