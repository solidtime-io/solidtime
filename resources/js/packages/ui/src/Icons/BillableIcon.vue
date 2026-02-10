<script setup lang="ts">
import { computed } from 'vue';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import DollarIcon from './DollarIcon.vue';
import EuroIcon from './EuroIcon.vue';

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const icon = computed(() => (organization.value?.currency === 'EUR' ? EuroIcon : DollarIcon));
</script>

<template>
    <component :is="icon" />
</template>
