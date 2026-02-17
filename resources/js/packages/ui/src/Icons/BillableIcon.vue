<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import type { Organization } from '@/packages/api/src';
import DollarIcon from './DollarIcon.vue';
import EuroIcon from './EuroIcon.vue';

const organization = inject<ComputedRef<Organization>>('organization');
const icon = computed(() => (organization?.value?.currency === 'EUR' ? EuroIcon : DollarIcon));
</script>

<template>
    <component :is="icon" />
</template>
