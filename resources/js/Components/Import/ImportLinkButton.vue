<script setup lang="ts">
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/16/solid';
import { Button } from '@/packages/ui/src/Buttons';

defineProps<{
    href: string;
    label: string;
}>();
</script>

<template>
    <Button as="a" variant="ghost" size="sm" :href="href" target="_blank" rel="noopener">
        {{ label }}
        <ArrowTopRightOnSquareIcon class="text-text-tertiary" />
    </Button>
</template>
