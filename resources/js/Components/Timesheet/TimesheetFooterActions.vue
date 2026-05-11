<script setup lang="ts">
import { Button } from '@/packages/ui/src/Buttons';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/packages/ui/src/dropdown-menu';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import { ChevronDownIcon, ClockIcon, ListBulletIcon } from '@heroicons/vue/20/solid';

defineProps<{
    busy: boolean;
}>();

defineEmits<{
    (e: 'copy-rows'): void;
    (e: 'copy-with-time'): void;
}>();
</script>

<template>
    <div class="mt-2 flex items-center pl-4 pr-4">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="sm" :disabled="busy">
                    <LoadingSpinner v-if="busy" class="h-3.5 w-3.5 m-0" />
                    Copy last week
                    <ChevronDownIcon v-if="!busy" class="h-3.5 w-3.5 ml-1 text-icon-default" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="min-w-[220px]">
                <DropdownMenuItem
                    class="flex items-center space-x-3 cursor-pointer"
                    @click="$emit('copy-rows')">
                    <ListBulletIcon class="w-5 text-icon-default" />
                    <span>Copy rows only</span>
                </DropdownMenuItem>
                <DropdownMenuItem
                    class="flex items-center space-x-3 cursor-pointer"
                    @click="$emit('copy-with-time')">
                    <ClockIcon class="w-5 text-icon-default" />
                    <span>Copy rows and time entries</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
