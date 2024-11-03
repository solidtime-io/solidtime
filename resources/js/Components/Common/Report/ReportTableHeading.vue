<script setup lang="ts">
import TableHeading from '@/Components/Common/TableHeading.vue';
</script>

<template>
    <TableHeading>
        <div
            class="py-1.5 pr-3 text-left font-semibold text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            Name
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-white">
            Description
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-white">
            Visibility
        </div>
        <div class="px-3 py-1.5 text-left font-semibold text-white">
            Public URL
        </div>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <span class="sr-only">Edit</span>
        </div>
    </TableHeading>
</template>

<style scoped></style>
