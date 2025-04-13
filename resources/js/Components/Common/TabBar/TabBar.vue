<script setup lang="ts">
import { Tabs, TabsList } from '@/Components/ui/tabs'

defineProps<{
    defaultValue?: string
}>()
</script>

<template>
    <Tabs :default-value="defaultValue" class="w-full">
        <TabsList class="flex items-center space-x-0.5 sm:space-x-1">
            <slot></slot>
        </TabsList>
    </Tabs>
</template>

<style scoped></style>
